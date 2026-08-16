<?php

namespace App\Features;

use App\Models\Organization;

/**
 * When active for an Organization, a Member is additionally allowed to
 * close (or cancel) a request *they created themselves* — normally only
 * Administrator/Manager/the assignee can change status at all.
 *
 * This is the project's example of "activar una funcionalidad
 * progresivamente": it is scoped per Organization (see config/pennant.php,
 * "database" store), so it can be turned on for one tenant to try it out
 * without affecting the rest — a real SaaS rollout pattern, not just an
 * on/off switch for the whole app.
 */
class MembersCanCloseOwnRequests
{
    public function resolve(Organization $organization): bool
    {
        return false;
    }
}
