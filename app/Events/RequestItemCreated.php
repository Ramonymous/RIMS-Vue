<?php

namespace App\Events;

use App\Models\RequestLists;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestItemCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RequestLists $requestItem)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('request-items'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'item' => [
                'id' => $this->requestItem->id,
                'part' => [
                    'id' => $this->requestItem->part->id,
                    'part_number' => $this->requestItem->part->part_number,
                    'part_name' => $this->requestItem->part->part_name,
                    'stock' => $this->requestItem->part->stock,
                ],
                'qty' => $this->requestItem->qty,
                'is_urgent' => $this->requestItem->is_urgent,
                'is_supplied' => $this->requestItem->is_supplied,
                'request' => [
                    'id' => $this->requestItem->request->id,
                    'request_number' => $this->requestItem->request->request_number,
                    'destination' => $this->requestItem->request->destination,
                    'requested_by' => [
                        'name' => $this->requestItem->request->requestedBy->name,
                    ],
                    'requested_at' => $this->requestItem->request->requested_at,
                ],
            ],
        ];
    }
}
