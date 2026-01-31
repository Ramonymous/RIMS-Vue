<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receivings extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'doc_number',
        'received_by',
        'received_at',
        'status',
        'notes',
        'is_gr',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'is_gr' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    // Relationships
    public function receivedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReceivingItems::class, 'receiving_id');
    }

    public function movements(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(PartMovements::class, 'reference');
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

    public function scopeGrConfirmed(Builder $query): Builder
    {
        return $query->where('is_gr', true);
    }

    public function scopePendingGr(Builder $query): Builder
    {
        return $query->where('is_gr', false)
            ->where('status', 'completed');
    }

    public function scopeByDocNumber(Builder $query, string $docNumber): Builder
    {
        return $query->where('doc_number', 'LIKE', "%{$docNumber}%");
    }

    // Business Logic Methods
    public function isEditable(): bool
    {
        return ! $this->is_gr && $this->status !== 'cancelled';
    }

    public function canBeGrConfirmed(): bool
    {
        return $this->status === 'completed' && ! $this->is_gr;
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('qty');
    }
}
