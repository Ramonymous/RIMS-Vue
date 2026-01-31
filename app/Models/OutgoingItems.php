<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutgoingItems extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'outgoing_id',
        'part_id',
        'qty',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
    }

    public function outgoing(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Outgoings::class, 'outgoing_id');
    }

    public function part(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Parts::class, 'part_id');
    }
}
