<?php

namespace App\DataTransferObjects;

class OutgoingData
{
    public function __construct(
        public string $docNumber,
        public string $issuedBy,
        public string $issuedAt,
        public string $status,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            docNumber: $data['doc_number'],
            issuedBy: $data['issued_by'] ?? auth()->id(),
            issuedAt: $data['issued_at'],
            status: $data['status'],
            notes: $data['notes'] ?? null,
            items: array_map(fn ($item) => OutgoingItemData::fromArray($item), $data['items']),
        );
    }

    public function toArray(): array
    {
        return [
            'doc_number' => $this->docNumber,
            'issued_by' => $this->issuedBy,
            'issued_at' => $this->issuedAt,
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

class OutgoingItemData
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
