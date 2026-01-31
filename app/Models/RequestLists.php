<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestLists extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'request_id',
        'part_id',
        'qty',
        'is_urgent',
        'is_supplied',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'is_urgent' => 'boolean',
            'is_supplied' => 'boolean',
        ];
    }

    // Relationships
    public function request(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Requests::class, 'request_id');
    }

    public function part(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Parts::class, 'part_id');
    }
}