<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartMovements extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    public const UPDATED_AT = null;

    protected $fillable = [
        'part_id',
        'stock_before',
        'type',
        'qty',
        'stock_after',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'stock_before' => 'integer',
            'qty' => 'integer',
            'stock_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function part(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Parts::class, 'part_id');
    }

    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
