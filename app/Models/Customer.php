<?php

namespace App\Models;

class Customer
{
    public function __construct(
        public readonly ?int    $id,
        public readonly string  $name,
        public readonly string  $address,
        public readonly string  $city,
        public readonly string  $contact,
        public readonly string  $phone,
        public readonly string  $vat,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id:        isset($row['id']) ? (int)$row['id'] : null,
            name:      $row['name']    ?? '',
            address:   $row['address'] ?? '',
            city:      $row['city']    ?? '',
            contact:   $row['contact'] ?? '',
            phone:     $row['phone']   ?? '',
            vat:       $row['vat']     ?? '',
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'address'    => $this->address,
            'city'       => $this->city,
            'contact'    => $this->contact,
            'phone'      => $this->phone,
            'vat'        => $this->vat,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
