<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parts extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'part_number',
        'part_name',
        'customer_code',
        'supplier_code',
        'model',
        'variant',
        'standard_packing',
        'stock',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'standard_packing' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    // Relationships
    public function receivingItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReceivingItems::class, 'part_id');
    }

    public function outgoingItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OutgoingItems::class, 'part_id');
    }

    public function movements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PartMovements::class, 'part_id');
    }

    // Query Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('stock', '<=', 0);
    }

    public function scopeLowStock(Builder $query, int $threshold = 10): Builder
    {
        return $query->where('stock', '>', 0)
            ->where('stock', '<=', $threshold);
    }

    public function scopeByPartNumber(Builder $query, string $partNumber): Builder
    {
        return $query->where('part_number', 'LIKE', "%{$partNumber}%");
    }

    public function scopeByPartName(Builder $query, string $partName): Builder
    {
        return $query->where('part_name', 'LIKE', "%{$partName}%");
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('part_number', 'LIKE', "%{$search}%")
                ->orWhere('part_name', 'LIKE', "%{$search}%")
                ->orWhere('customer_code', 'LIKE', "%{$search}%")
                ->orWhere('supplier_code', 'LIKE', "%{$search}%")
                ->orWhere('model', 'LIKE', "%{$search}%");
        });
    }

    // Accessors
    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'out_of_stock';
        }

        if ($this->stock <= 10) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public function hasTransactions(): bool
    {
        return $this->receivingItems()->exists() || $this->outgoingItems()->exists();
    }
}
