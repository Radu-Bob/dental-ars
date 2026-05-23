<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
        ];
    }

     /**
     * Accessor to check if the user is an admin.
     * This allows us to use Auth::user()->is_admin.
     * @return bool
     */
    public function getIsAdminAttribute(): bool
    {
        // Using the cleaner $this->role syntax, as requested.
        return $this->role === 'admin';
    }

    /**
     * Accessor to check if the user is a doctor.
     * This allows us to use Auth::user()->is_doctor.
     * @return bool
     */

    public function getIsDoctorAttribute(): bool
    {
        // Replace 'role' with whatever column you use to identify a doctor
        // so th eexport button is visible in the doctor reports
        return $this->role === 'doctor';
    }

    public function getIsNurseAttribute(): bool
    {
        return $this->role === 'nurse';
    }
}
