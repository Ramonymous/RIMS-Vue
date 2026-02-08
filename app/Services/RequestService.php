<?php

namespace App\Services;

use App\Events\RequestItemCreated;
use App\Models\RequestLists;
use App\Models\Requests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestService
{
    /**
     * Create a new request with items (NO stock movements)
     */
    public function create(array $data): Requests
    {
        return DB::transaction(function () use ($data) {
            $request = Requests::create([
                'request_number' => $data['request_number'],
                'requested_by' => $data['requested_by'],
                'requested_at' => $data['requested_at'],
                'destination' => $data['destination'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $this->addItem($request, $item);
            }

            Log::info('Request created', ['request_id' => $request->id, 'request_number' => $request->request_number]);

            return $request->load(['items.part', 'requestedBy']);
        });
    }

    /**
     * Update an existing request
     */
    public function update(Requests $request, array $data): Requests
    {
        return DB::transaction(function () use ($request, $data) {
            $request->update([
                'request_number' => $data['request_number'],
                'requested_at' => $data['requested_at'],
                'destination' => $data['destination'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Replace all items
            $this->replaceItems($request, $data['items']);

            Log::info('Request updated', ['request_id' => $request->id]);

            return $request->fresh(['items.part', 'requestedBy']);
        });
    }

    /**
     * Cancel a request
     */
    public function cancel(Requests $request): Requests
    {
        return DB::transaction(function () use ($request) {
            $request->update(['status' => 'cancelled']);

            Log::info('Request cancelled', ['request_id' => $request->id]);

            return $request;
        });
    }

    /**
     * Add an item to a request
     */
    private function addItem(Requests $request, array $itemData): RequestLists
    {
        $createdItem = RequestLists::create([
            'request_id' => $request->id,
            'part_id' => $itemData['part_id'],
            'qty' => $itemData['qty'],
            'is_urgent' => $itemData['is_urgent'] ?? false,
            'is_supplied' => $itemData['is_supplied'] ?? false,
        ]);

        // Load relationships for broadcasting
        $createdItem->load(['part', 'request.requestedBy']);

        // Broadcast the event
        try {
            broadcast(new RequestItemCreated($createdItem));
        } catch (\Throwable $e) {
            Log::warning('RequestItemCreated broadcast failed', [
                'request_item_id' => $createdItem->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $createdItem;
    }

    /**
     * Replace all items in a request
     */
    private function replaceItems(Requests $request, array $items): void
    {
        $request->items()->delete();

        // Bulk insert for better performance
        $createdItems = [];
        foreach ($items as $item) {
            $createdItems[] = [
                'request_id' => $request->id,
                'part_id' => $item['part_id'],
                'qty' => $item['qty'],
                'is_urgent' => $item['is_urgent'] ?? false,
                'is_supplied' => $item['is_supplied'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        RequestLists::insert($createdItems);
        
        // Broadcast events for newly created items
        $request->load('items.part', 'requestedBy');
        foreach ($request->items as $item) {
            broadcast(new RequestItemCreated($item));
        }
    }
}
