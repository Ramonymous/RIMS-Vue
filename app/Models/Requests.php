<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requests extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'request_number',
        'requested_by',
        'requested_at',
        'destination',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relationships
    public function requestedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RequestLists::class, 'request_id');
    }

    // Query Scopes
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByRequestNumber(Builder $query, string $requestNumber): Builder
    {
        return $query->where('request_number', 'LIKE', "%{$requestNumber}%");
    }
}
