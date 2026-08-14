<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends TenantScopedModel
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RequestStatusHistory::class)->latest();
    }
}
