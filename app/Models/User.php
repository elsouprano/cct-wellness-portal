<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
        'is_paying_student',
        'role',
        'email',
        'student_id',
        'password',
        'year_level',
        'year_level_confirmed',
        'profile_picture_path',
        'program_id',
    ];

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
        ];
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
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
            return \Illuminate\Support\Facades\Storage::url($this->profile_picture_path);
        }

        $initials = strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
        
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <rect width="100" height="100" fill="#4f7c68" />
            <text x="50" y="50" font-family="sans-serif" font-size="40" font-weight="bold" fill="#ffffff" dominant-baseline="central" text-anchor="middle">' . htmlspecialchars($initials) . '</text>
        </svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
