<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\InsufficientStockException;
use App\Models\Outgoings;
use App\Models\OutgoingItems;
use App\Models\PartMovements;
use App\Models\Parts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutgoingService
{
    /**
     * Create a new outgoing with items
     */
    public function create(array $data): Outgoings
    {
        return DB::transaction(function () use ($data) {
            $outgoing = Outgoings::create([
                'doc_number' => $data['doc_number'],
                'issued_by' => $data['issued_by'],
                'issued_at' => $data['issued_at'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'is_gi' => false,
            ]);

            foreach ($data['items'] as $item) {
                $this->addItem($outgoing, $item);
            }

            // If status is completed, confirm all stock movements
            if ($data['status'] === 'completed') {
                $this->confirmOutgoing($outgoing);
            }

            Log::info('Outgoing created', ['outgoing_id' => $outgoing->id, 'doc_number' => $outgoing->doc_number]);

            return $outgoing->load(['items.part', 'issuedBy']);
        });
    }

    /**
     * Update an existing outgoing
     */
    public function update(Outgoings $outgoing, array $data): Outgoings
    {
        $this->validateOutgoingEditable($outgoing);

        return DB::transaction(function () use ($outgoing, $data) {
            $wasCompleted = $outgoing->status === 'completed';
            $nowCompleted = $data['status'] === 'completed';

            // Reverse stock if changing from completed to draft
            if ($wasCompleted && ! $nowCompleted) {
                $this->reverseStockMovements($outgoing);
            }

            $outgoing->update([
                'doc_number' => $data['doc_number'],
                'issued_at' => $data['issued_at'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Replace all items
            $this->replaceItems($outgoing, $data['items']);

            // Apply stock if completing
            if ($nowCompleted && ! $wasCompleted) {
                $this->confirmOutgoing($outgoing);
            }

            Log::info('Outgoing updated', ['outgoing_id' => $outgoing->id]);

            return $outgoing->fresh(['items.part', 'issuedBy']);
        });
    }

    /**
     * Cancel an outgoing and reverse stock movements
     */
    public function cancel(Outgoings $outgoing): Outgoings
    {
        return DB::transaction(function () use ($outgoing) {
            if ($outgoing->status === 'completed') {
                $this->reverseStockMovements($outgoing);
            }

            $outgoing->update(['status' => 'cancelled']);

            Log::info('Outgoing cancelled', ['outgoing_id' => $outgoing->id]);

            return $outgoing;
        });
    }

    /**
     * Toggle GI status
     */
    public function toggleGiStatus(Outgoings $outgoing): Outgoings
    {
        $outgoing->update(['is_gi' => ! $outgoing->is_gi]);

        Log::info('Outgoing GI status toggled', [
            'outgoing_id' => $outgoing->id,
            'is_gi' => $outgoing->is_gi,
        ]);

        return $outgoing;
    }

    /**
     * Add an item to an outgoing
     */
    private function addItem(Outgoings $outgoing, array $itemData): OutgoingItems
    {
        return OutgoingItems::create([
            'outgoing_id' => $outgoing->id,
            'part_id' => $itemData['part_id'],
            'qty' => $itemData['qty'],
        ]);
    }

    /**
     * Replace all items in an outgoing
     */
    private function replaceItems(Outgoings $outgoing, array $items): void
    {
        $outgoing->items()->delete();

        // Bulk insert - single query instead of N queries
        $outgoing->items()->createMany($items);
    }

    /**
     * Confirm outgoing and apply stock movements
     */
    private function confirmOutgoing(Outgoings $outgoing): void
    {
        $outgoing->loadMissing('items.part');
        
        foreach ($outgoing->items as $item) {
            $this->applyStockMovement($item->part, $item->qty, $outgoing);
        }
    }

    /**
     * Apply stock movement for a part
     */
    private function applyStockMovement(Parts $part, int $qty, Outgoings $outgoing): void
    {
        $stockBefore = $part->stock;
        $stockAfter = $stockBefore - $qty;

        if ($stockAfter < 0) {
            throw new InsufficientStockException($part->part_number, $stockBefore, $qty);
        }

        $part->update(['stock' => $stockAfter]);

        PartMovements::create([
            'part_id' => $part->id,
            'stock_before' => $stockBefore,
            'type' => 'out',
            'qty' => $qty,
            'stock_after' => $stockAfter,
            'reference_type' => Outgoings::class,
            'reference_id' => $outgoing->id,
        ]);

        Log::debug('Stock movement applied', [
            'part_id' => $part->id,
            'qty' => $qty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
        ]);
    }

    /**
     * Reverse all stock movements for an outgoing
     */
    private function reverseStockMovements(Outgoings $outgoing): void
    {
        $outgoing->loadMissing('items.part');
        
        foreach ($outgoing->items as $item) {
            $part = $item->part;
            $stockBefore = $part->stock;
            $stockAfter = $stockBefore + $item->qty;

            $part->update(['stock' => $stockAfter]);

            // Delete movement records
            PartMovements::where('reference_type', Outgoings::class)
                ->where('reference_id', $outgoing->id)
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
     * Validate that outgoing can be edited
     */
    private function validateOutgoingEditable(Outgoings $outgoing): void
    {
        if ($outgoing->is_gi) {
            throw new BusinessException('Cannot edit outgoing that has been GI confirmed', 403);
        }

        if ($outgoing->status === 'cancelled') {
            throw new BusinessException('Cannot edit cancelled outgoing', 403);
        }
    }
}
