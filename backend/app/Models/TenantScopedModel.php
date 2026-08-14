<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for every model that belongs to a single Organization.
 *
 * Extending this class (instead of Model) is what guarantees tenant
 * isolation: it registers the OrganizationScope global scope and
 * auto-fills organization_id on creation. See docs/adr/0003.
 */
abstract class TenantScopedModel extends Model
{
    use BelongsToOrganization;
}
