<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingItems extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'receiving_id',
        'part_id',
        'qty',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
    }

    public function receiving(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Receivings::class, 'receiving_id');
    }

    public function part(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Parts::class, 'part_id');
    }
}
