<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_initial',
        'last_name',
        'birthdate',
        'program',
        'section',
        'contact_number',
        'address_line1',
        'city',
        'province',
        'email',
        'password',
        'year_level',
        'profile_picture_path',
        'program_id',
        'is_active',
        'deactivated_at',
        'deactivated_by',
        'needs_section_confirmation',
    ];

    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function structuredProgram()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
            'is_paying_student' => 'boolean',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'needs_section_confirmation' => 'boolean',
        ];
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }

    public function inventorySubmissions()
    {
        return $this->hasMany(InventorySubmission::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    public function isCounselor(): bool
    {
        return $this->role === 'guidance_counselor';
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile_picture_path) {
            return Storage::url($this->profile_picture_path);
        }

        $initials = strtoupper(substr($this->first_name, 0, 1).substr($this->last_name, 0, 1));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <rect width="100" height="100" fill="#4f7c68" />
            <text x="50" y="50" font-family="sans-serif" font-size="40" font-weight="bold" fill="#ffffff" dominant-baseline="central" text-anchor="middle">'.htmlspecialchars($initials).'</text>
        </svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function syncSectionToYearLevel(string $newLevel): bool
    {
        $map = [
            '1st' => '1',
            '2nd' => '2',
            '3rd' => '3',
            '4th' => '4',
        ];

        if (! isset($map[$newLevel])) {
            return true;
        }

        $digit = $map[$newLevel];

        if (empty($this->section) || $this->section === 'N/A') {
            return false;
        }

        if (preg_match('/^\d(?=[-\s])/', $this->section)) {
            $this->section = preg_replace('/^\d/', $digit, $this->section);

            return true;
        }

        return false;
    }
}
