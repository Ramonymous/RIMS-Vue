<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'part_number' => $this->part->part_number,
            'date' => $this->created_at->format('Y-m-d'),
            'time' => $this->created_at->format('H:i:s'),
            'type' => strtoupper($this->type),
            'qty' => $this->qty,
        ];
    }
}
