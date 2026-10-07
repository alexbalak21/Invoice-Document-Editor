<?php

namespace App\Models;

/**
 * Document — value object representing a document row + its JSON data blob.
 */
class Document
{
    public function __construct(
        public readonly ?int    $id,
        public readonly string  $type,
        public readonly string  $number,
        public readonly string  $date,
        public readonly string  $status,
        public readonly string  $customer,
        public readonly array   $data,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id:        (int) $row['id'],
            type:      $row['type']     ?? 'INVOICE',
            number:    $row['number']   ?? '',
            date:      $row['date']     ?? '',
            status:    $row['status']   ?? 'draft',
            customer:  $row['customer'] ?? '',
            data:      is_string($row['data'] ?? null)
                           ? (json_decode($row['data'], true) ?? [])
                           : ($row['data'] ?? []),
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'number'     => $this->number,
            'date'       => $this->date,
            'status'     => $this->status,
            'customer'   => $this->customer,
            'data'       => $this->data,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /** Summary — list view only, no data blob */
    public function toSummary(): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'number'     => $this->number,
            'date'       => $this->date,
            'status'     => $this->status,
            'customer'   => $this->customer,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
