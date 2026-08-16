<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Features\MembersCanCloseOwnRequests;
use App\Models\Request;
use App\Models\User;
use Laravel\Pennant\Feature;

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

    public function changeStatus(User $user, Request $request, RequestStatus $newStatus): bool
    {
        if ($user->organization_id !== $request->organization_id) {
            return false;
        }

        if (in_array($user->role, [UserRole::Administrator, UserRole::Manager], true)
            || $user->id === $request->assigned_to) {
            return true;
        }

        // Progressive rollout example (see App\Features\MembersCanCloseOwnRequests):
        // when active for this organization, a Member may close a request
        // they created themselves, even without being the assignee.
        return $newStatus === RequestStatus::Closed
            && $user->id === $request->created_by
            && Feature::for($user->organization)->active(MembersCanCloseOwnRequests::class);
    }
}
