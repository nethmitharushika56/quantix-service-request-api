<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'manager_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Employees who report to this manager
    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    // Manager responsible for this employee
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Requests created by this employee
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'employee_id');
    }

    // Requests assigned to this operations user
    public function assignedRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'assigned_to');
    }

    // Comments written by this user
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // Status changes performed by this user
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class, 'actor_id');
    }
}
