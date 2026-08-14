<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestStatusHistory extends TenantScopedModel
{
    protected $fillable = [
        'organization_id',
        'request_id',
        'changed_by',
        'from_status',
        'to_status',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => RequestStatus::class,
            'to_status' => RequestStatus::class,
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
