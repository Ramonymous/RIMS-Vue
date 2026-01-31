<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Parts;
use App\Models\PartMovements;
use App\Models\Receivings;
use App\Models\ReceivingItems;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceivingService
{
    /**
     * Create a new receiving with items
     */
    public function create(array $data): Receivings
    {
        return DB::transaction(function () use ($data) {
            $receiving = Receivings::create([
                'doc_number' => $data['doc_number'],
                'received_by' => $data['received_by'],
                'received_at' => $data['received_at'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'is_gr' => false,
            ]);

            foreach ($data['items'] as $item) {
                $this->addItem($receiving, $item);
            }

            // If status is completed, confirm all stock movements
            if ($data['status'] === 'completed') {
                $this->confirmReceiving($receiving);
            }

            Log::info('Receiving created', ['receiving_id' => $receiving->id, 'doc_number' => $receiving->doc_number]);

            return $receiving->load(['items.part', 'receivedBy']);
        });
    }

    /**
     * Update an existing receiving
     */
    public function update(Receivings $receiving, array $data): Receivings
    {
        $this->validateReceivingEditable($receiving);

        return DB::transaction(function () use ($receiving, $data) {
            $wasCompleted = $receiving->status === 'completed';
            $nowCompleted = $data['status'] === 'completed';

            // Reverse stock if changing from completed to draft
            if ($wasCompleted && ! $nowCompleted) {
                $this->reverseStockMovements($receiving);
            }

            $receiving->update([
                'doc_number' => $data['doc_number'],
                'received_at' => $data['received_at'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Replace all items
            $this->replaceItems($receiving, $data['items']);

            // Apply stock if completing
            if ($nowCompleted && ! $wasCompleted) {
                $this->confirmReceiving($receiving);
            }

            Log::info('Receiving updated', ['receiving_id' => $receiving->id]);

            return $receiving->fresh(['items.part', 'receivedBy']);
        });
    }

    /**
     * Cancel a receiving and reverse stock movements
     */
    public function cancel(Receivings $receiving): Receivings
    {
        return DB::transaction(function () use ($receiving) {
            if ($receiving->status === 'completed') {
                $this->reverseStockMovements($receiving);
            }

            $receiving->update(['status' => 'cancelled']);

            Log::info('Receiving cancelled', ['receiving_id' => $receiving->id]);

            return $receiving;
        });
    }

    /**
     * Toggle GR status
     */
    public function toggleGrStatus(Receivings $receiving): Receivings
    {
        $receiving->update(['is_gr' => ! $receiving->is_gr]);

        Log::info('Receiving GR status toggled', [
            'receiving_id' => $receiving->id,
            'is_gr' => $receiving->is_gr,
        ]);

        return $receiving;
    }

    /**
     * Add an item to a receiving
     */
    private function addItem(Receivings $receiving, array $itemData): ReceivingItems
    {
        return ReceivingItems::create([
            'receiving_id' => $receiving->id,
            'part_id' => $itemData['part_id'],
            'qty' => $itemData['qty'],
        ]);
    }

    /**
     * Replace all items in a receiving
     */
    private function replaceItems(Receivings $receiving, array $items): void
    {
        $receiving->items()->delete();

        // Bulk insert - single query instead of N queries
        $receiving->items()->createMany($items);
    }

    /**
     * Confirm receiving and apply stock movements
     */
    private function confirmReceiving(Receivings $receiving): void
    {
        $receiving->loadMissing('items.part');
        
        foreach ($receiving->items as $item) {
            $this->applyStockMovement($item->part, $item->qty, $receiving);
        }
    }

    /**
     * Apply stock movement for a part
     */
    private function applyStockMovement(Parts $part, int $qty, Receivings $receiving): void
    {
        $stockBefore = $part->stock;
        $stockAfter = $stockBefore + $qty;

        $part->update(['stock' => $stockAfter]);

        PartMovements::create([
            'part_id' => $part->id,
            'stock_before' => $stockBefore,
            'type' => 'in',
            'qty' => $qty,
            'stock_after' => $stockAfter,
            'reference_type' => Receivings::class,
            'reference_id' => $receiving->id,
        ]);

        Log::debug('Stock movement applied', [
            'part_id' => $part->id,
            'qty' => $qty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
        ]);
    }

    /**
     * Reverse all stock movements for a receiving
     */
    private function reverseStockMovements(Receivings $receiving): void
    {
        $receiving->loadMissing('items.part');
        
        foreach ($receiving->items as $item) {
            $part = $item->part;
            $stockBefore = $part->stock;
            $stockAfter = $stockBefore - $item->qty;

            if ($stockAfter < 0) {
                throw new BusinessException(
                    "Cannot reverse receiving: insufficient stock for part {$part->part_number}",
                    422,
                    ['part_number' => $part->part_number, 'available' => $stockBefore, 'required' => $item->qty]
                );
            }

            $part->update(['stock' => $stockAfter]);

            // Delete movement records
            PartMovements::where('reference_type', Receivings::class)
                ->where('reference_id', $receiving->id)
                ->where('part_id', $part->id)
                ->delete();

            Log::debug('Stock movement reversed', [
                'part_id' => $part->id,
                'qty' => $item->qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
            ]);
        }
    }

    /**
     * Validate that receiving can be edited
     */
    private function validateReceivingEditable(Receivings $receiving): void
    {
        if ($receiving->is_gr) {
            throw new BusinessException('Cannot edit receiving that has been GR confirmed', 403);
        }

        if ($receiving->status === 'cancelled') {
            throw new BusinessException('Cannot edit cancelled receiving', 403);
        }
    }
}
