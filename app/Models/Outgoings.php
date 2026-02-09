<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outgoings extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'doc_number',
        'issued_by',
        'issued_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relationships
    public function issuedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OutgoingItems::class, 'outgoing_id');
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

    public function scopeGiConfirmed(Builder $query): Builder
    {
        return $query->where('is_gi', true);
    }

    public function scopePendingGi(Builder $query): Builder
    {
        return $query->where('is_gi', false)
            ->where('status', 'completed');
    }

    public function scopeByDocNumber(Builder $query, string $docNumber): Builder
    {
        return $query->where('doc_number', 'LIKE', "%{$docNumber}%");
    }

    // Business Logic Methods
    public function isEditable(): bool
    {
        return ! $this->is_gi && $this->status !== 'cancelled';
    }

    public function canBeGiConfirmed(): bool
    {
        return $this->status === 'completed' && ! $this->is_gi;
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('qty');
    }
}
