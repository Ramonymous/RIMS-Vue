<?php

namespace App\DataTransferObjects;

class ReceivingData
{
    public function __construct(
        public string $docNumber,
        public string $receivedBy,
        public string $receivedAt,
        public string $status,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            docNumber: $data['doc_number'],
            receivedBy: $data['received_by'] ?? auth()->id(),
            receivedAt: $data['received_at'],
            status: $data['status'],
            notes: $data['notes'] ?? null,
            items: array_map(fn ($item) => ReceivingItemData::fromArray($item), $data['items']),
        );
    }

    public function toArray(): array
    {
        return [
            'doc_number' => $this->docNumber,
            'received_by' => $this->receivedBy,
            'received_at' => $this->receivedAt,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => array_map(fn ($item) => $item->toArray(), $this->items),
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->docNumber)) {
            $errors['doc_number'][] = 'Document number is required';
        }

        if (! in_array($this->status, ['draft', 'completed', 'cancelled'])) {
            $errors['status'][] = 'Invalid status';
        }

        if (empty($this->items)) {
            $errors['items'][] = 'At least one item is required';
        }

        foreach ($this->items as $index => $item) {
            $itemErrors = $item->validate();
            if (! empty($itemErrors)) {
                $errors["items.{$index}"] = $itemErrors;
            }
        }

        return $errors;
    }
}

class ReceivingItemData
{
    public function __construct(
        public string $partId,
        public int $qty,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            partId: $data['part_id'],
            qty: (int) $data['qty'],
        );
    }

    public function toArray(): array
    {
        return [
            'part_id' => $this->partId,
            'qty' => $this->qty,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->partId)) {
            $errors['part_id'][] = 'Part is required';
        }

        if ($this->qty <= 0) {
            $errors['qty'][] = 'Quantity must be greater than 0';
        }

        return $errors;
    }
}
