<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'employee_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isContractor(): bool
    {
        return $this->role === 'contractor';
    }

    public function canInspect(): bool
    {
        return in_array($this->role, ['admin', 'inspector']);
    }

    public function canManageUsers(): bool
    {
        return $this->role === 'admin';
    }

    public function getRoleLabel(): string
    {
        return match($this->role) {
            'admin'      => 'Admin',
            'inspector'  => 'Inspektor',
            'contractor' => 'Kontraktor',
            default      => ucfirst($this->role),
        };
    }
}
