<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Request;
use App\Models\User;

/**
 * Tenant isolation (can user A even reach organization B's request) is
 * enforced separately by the OrganizationScope global scope + route model
 * binding. This policy only decides "what can this role do", assuming the
 * request already belongs to the user's organization.
 */
class RequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Request $request): bool
    {
        return $user->organization_id === $request->organization_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function comment(User $user, Request $request): bool
    {
        return $user->organization_id === $request->organization_id;
    }

    public function changeStatus(User $user, Request $request): bool
    {
        if ($user->organization_id !== $request->organization_id) {
            return false;
        }

        return in_array($user->role, [UserRole::Administrator, UserRole::Manager], true)
            || $user->id === $request->assigned_to;
    }
}
