<?php

namespace App\Models;

class Item
{
    public function __construct(
        public readonly ?int    $id,
        public readonly string  $reference,
        public readonly string  $title,
        public readonly string  $unit,
        public readonly string  $price,
        public readonly string  $description,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id:          isset($row['id']) ? (int)$row['id'] : null,
            reference:   $row['reference']   ?? '',
            title:       $row['title']       ?? '',
            unit:        $row['unit']        ?? '',
            price:       $row['price']       ?? '',
            description: $row['description'] ?? '',
            createdAt:   $row['created_at']  ?? null,
            updatedAt:   $row['updated_at']  ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'reference'   => $this->reference,
            'title'       => $this->title,
            'unit'        => $this->unit,
            'price'       => $this->price,
            'description' => $this->description,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }
}
