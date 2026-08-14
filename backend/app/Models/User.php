<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User deliberately does NOT use the BelongsToOrganization trait, unlike
 * every other tenant-owned model. Authenticating a request resolves the
 * User by session/token first — if that lookup were itself filtered by
 * "the authenticated user's organization_id" (via the OrganizationScope
 * global scope), resolving the user would require the user to already be
 * resolved, an infinite recursion that exhausts PHP's memory limit. See
 * ADR 0003 (amendment) for the full explanation.
 *
 * This means queries like `User::all()` are NOT automatically tenant-scoped.
 * Anywhere the app lists or looks up other users (e.g. a future "manage
 * users" screen), the controller must filter by organization_id explicitly.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'password',
        'role',
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
            'role' => UserRole::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'assigned_to');
    }

    public function createdRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }
}
