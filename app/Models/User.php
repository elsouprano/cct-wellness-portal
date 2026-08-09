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
        'password',
        'year_level',
        'year_level_confirmed',
    ];

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
}
