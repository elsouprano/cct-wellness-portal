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
        $user->fill($request->safe()->except('profile_picture'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            
            // Delete old picture if exists
            if ($user->profile_picture_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_picture_path);
            }

            $extension = $file->getClientOriginalExtension();
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $extension;
            $path = $file->storeAs('profile-pictures', $filename, 'public');

            // Native GD library cropping and resizing to 500x500
            $fullPath = storage_path('app/public/' . $path);
            
            $mime = mime_content_type($fullPath);
            if ($mime == 'image/jpeg') {
                $image = imagecreatefromjpeg($fullPath);
            } elseif ($mime == 'image/png') {
                $image = imagecreatefrompng($fullPath);
            } else {
                $image = imagecreatefromstring(file_get_contents($fullPath));
            }

            if ($image !== false) {
                $width = imagesx($image);
                $height = imagesy($image);
                
                // Calculate square crop
                $size = min($width, $height);
                $x = ($width - $size) / 2;
                $y = ($height - $size) / 2;
                
                $square = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => $size, 'height' => $size]);
                
                if ($square !== false) {
                    $resized = imagecreatetruecolor(500, 500);
                    
                    // Handle transparency for PNGs
                    if ($mime == 'image/png') {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                        imagefilledrectangle($resized, 0, 0, 500, 500, $transparent);
                    }
                    
                    imagecopyresampled($resized, $square, 0, 0, 0, 0, 500, 500, $size, $size);
                    
                    // Save back
                    if ($mime == 'image/jpeg') {
                        imagejpeg($resized, $fullPath, 90);
                    } elseif ($mime == 'image/png') {
                        imagepng($resized, $fullPath, 9);
                    }
                    
                    imagedestroy($square);
                    imagedestroy($resized);
                }
                imagedestroy($image);
            }

            $user->profile_picture_path = $path;
        }

        $user->save();

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

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
