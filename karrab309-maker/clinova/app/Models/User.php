<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        /** Spécialité médicale (rôle Doctor uniquement ; nullable pour les autres rôles). */
        'specialty',
        'locale',
        'profile_photo_path',
    ];

    protected $appends = ['profile_photo_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role): bool
    {
        return strcasecmp((string) $this->role, (string) $role) === 0;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->hasRole('Admin');
    }

    /**
     * Check if user is doctor
     */
    public function isDoctor()
    {
        return $this->hasRole('Doctor');
    }

    /**
     * Check if user is nurse (infirmier)
     */
    public function isNurse()
    {
        return $this->hasRole('Nurse');
    }

    /**
     * Check if user is patient
     */
    public function isPatient()
    {
        return $this->hasRole('Patient');
    }

    public function isLaboratory()
    {
        return $this->hasRole('Laboratory');
    }

    public function isAccountant()
    {
        return $this->hasRole('Accountant');
    }

    public function isSecretary()
    {
        return $this->hasRole('Secretary');
    }

    /**
     * URL publique de la photo de profil (visible par le médecin pour les patients).
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo_path) {
            return null;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->profile_photo_path);
    }
}
