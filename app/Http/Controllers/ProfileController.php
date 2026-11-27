<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $allowedProfileFields = ['first_name', 'last_name', 'email', 'section', 'contact_number', 'address_line1', 'city', 'province', 'gender'];
        $oldValues = $user->only($allowedProfileFields);
        $user->fill($request->safe()->except(['profile_picture', 'gender']));

        if (empty($user->gender) && $request->filled('gender')) {
            $user->gender = $request->validated('gender');
        }

        $oldPicturePath = null;
        $newPicturePath = null;

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $oldPicturePath = $user->profile_picture_path;

            $extension = $file->extension() ?: 'jpg';
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $extension;
            $path = $file->storeAs('profile-pictures', $filename, 'public');
            $newPicturePath = $path;

            // Native GD library cropping and resizing to 500x500 (if GD is available)
            if (extension_loaded('gd')) {
                $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);

                try {
                    $mime = @mime_content_type($fullPath) ?: $file->getMimeType();
                    if ($mime === 'image/jpeg') {
                        $image = @imagecreatefromjpeg($fullPath);
                    } elseif ($mime === 'image/png') {
                        $image = @imagecreatefrompng($fullPath);
                    } else {
                        $raw = @file_get_contents($fullPath);
                        $image = $raw !== false ? @imagecreatefromstring($raw) : false;
                    }

                    if ($image === false) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);

                        return back()->withInput()->withErrors([
                            'profile_picture' => 'The uploaded image could not be processed. Please upload a valid JPG or PNG file.',
                        ]);
                    }

                    $width = imagesx($image);
                    $height = imagesy($image);

                    // Calculate square crop
                    $size = min($width, $height);
                    $x = (int) floor(($width - $size) / 2);
                    $y = (int) floor(($height - $size) / 2);

                    $square = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => $size, 'height' => $size]);

                    if ($square !== false) {
                        $resized = imagecreatetruecolor(500, 500);

                        // Handle transparency for PNGs
                        if ($mime === 'image/png') {
                            imagealphablending($resized, false);
                            imagesavealpha($resized, true);
                            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                            imagefilledrectangle($resized, 0, 0, 500, 500, $transparent);
                        }

                        imagecopyresampled($resized, $square, 0, 0, 0, 0, 500, 500, $size, $size);

                        if ($mime === 'image/jpeg') {
                            imagejpeg($resized, $fullPath, 90);
                        } elseif ($mime === 'image/png') {
                            imagepng($resized, $fullPath, 9);
                        }

                        imagedestroy($square);
                        imagedestroy($resized);
                    }
                    imagedestroy($image);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);

                    return back()->withInput()->withErrors([
                        'profile_picture' => 'The uploaded image could not be processed. Please upload a valid JPG or PNG file.',
                    ]);
                }
            }

            $user->profile_picture_path = $path;
        }

        try {
            $user->save();
        } catch (\Throwable $e) {
            if ($newPicturePath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($newPicturePath);
            }
            throw $e;
        }

        if ($oldPicturePath && $newPicturePath && $oldPicturePath !== $newPicturePath) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPicturePath);
        }

        $newValues = $user->only($allowedProfileFields);
        $changedOld = [];
        $changedNew = [];
        foreach ($allowedProfileFields as $field) {
            if (($oldValues[$field] ?? null) !== ($newValues[$field] ?? null)) {
                $changedOld[$field] = $oldValues[$field] ?? null;
                $changedNew[$field] = $newValues[$field] ?? null;
            }
        }

        if (($user->isAdmin() || $user->isCounselor()) && (! empty($changedNew) || $request->hasFile('profile_picture'))) {
            app(\App\Services\ActivityLogService::class)->log(
                action: 'student.profile_updated',
                module: 'Settings',
                description: "{$user->first_name} {$user->last_name} updated their profile details.",
                subject: $user,
                oldValues: $changedOld ?: null,
                newValues: $changedNew ?: ($request->hasFile('profile_picture') ? ['profile_picture' => 'updated'] : null),
                allowedFields: array_merge($allowedProfileFields, ['profile_picture']),
                actor: $user,
            );
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $errorMessage = null;
        $profilePicturePath = $user->profile_picture_path;

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, &$errorMessage) {
            $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            // Prevent self-deletion if user has sensitive clinical/assessment records, authored announcements, or is the last active admin
            if ($lockedUser->inventorySubmissions()->exists()) {
                $errorMessage = 'Cannot delete an account with existing wellness assessment records. Please contact guidance staff.';

                return;
            }

            if (\App\Models\Announcement::where('user_id', $lockedUser->id)->exists()) {
                $errorMessage = 'Cannot delete an account that owns published or draft announcements.';

                return;
            }

            if ($lockedUser->isAdmin() && \App\Models\User::where('role', 'system_admin')->where('is_active', true)->lockForUpdate()->count() <= 1) {
                $errorMessage = 'Cannot delete the only active system administrator account.';

                return;
            }

            if ($lockedUser->isAdmin() || $lockedUser->isCounselor()) {
                app(\App\Services\ActivityLogService::class)->log(
                    action: 'account.self_deleted',
                    module: 'Accounts',
                    description: "{$lockedUser->first_name} {$lockedUser->last_name} deleted their own account.",
                    oldValues: ['email' => $lockedUser->email, 'role' => $lockedUser->role],
                    actor: $lockedUser,
                    subjectTypeOverride: \App\Models\User::class,
                    subjectIdOverride: $lockedUser->id,
                );
            }

            $lockedUser->delete();
        });

        if ($errorMessage) {
            return back()->withErrors(['error' => $errorMessage], 'userDeletion');
        }

        if ($profilePicturePath) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($profilePicturePath);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
