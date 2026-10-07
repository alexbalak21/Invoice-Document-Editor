<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Document;
use PDO;

class DocumentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** @return Document[] */
    public function findAll(string $search = '', string $type = ''): array
    {
        $sql    = 'SELECT id, type, number, date, status, customer, created_at, updated_at FROM documents WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (number LIKE :q OR customer LIKE :q)';
            $params[':q'] = "%$search%";
        }
        if ($type !== '') {
            $sql .= ' AND type = :type';
            $params[':type'] = $type;
        }
        $sql .= ' ORDER BY updated_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(
            fn(array $row) => Document::fromRow($row),
            $stmt->fetchAll()
        );
    }

    public function findById(int $id): ?Document
    {
        $stmt = $this->db->prepare('SELECT * FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Document::fromRow($row) : null;
    }

    public function create(Document $doc): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO documents (type, number, date, status, customer, data)
            VALUES (:type, :number, :date, :status, :customer, :data)
        ');
        $stmt->execute([
            ':type'     => $doc->type,
            ':number'   => $doc->number,
            ':date'     => $doc->date,
            ':status'   => $doc->status,
            ':customer' => $doc->customer,
            ':data'     => json_encode($doc->data, JSON_UNESCAPED_UNICODE),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, Document $doc): void
    {
        $stmt = $this->db->prepare('
            UPDATE documents
            SET type=:type, number=:number, date=:date, status=:status,
                customer=:customer, data=:data, updated_at=NOW()
            WHERE id=:id
        ');
        $stmt->execute([
            ':type'     => $doc->type,
            ':number'   => $doc->number,
            ':date'     => $doc->date,
            ':status'   => $doc->status,
            ':customer' => $doc->customer,
            ':data'     => json_encode($doc->data, JSON_UNESCAPED_UNICODE),
            ':id'       => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM documents WHERE id = ?')->execute([$id]);
    }

    public function duplicate(int $id): int
    {
        $doc = $this->findById($id);
        if (!$doc) throw new \RuntimeException('Document not found');

        $data           = $doc->data;
        $data['number'] = ($data['number'] ?? '') . '-COPY';

        $copy = new Document(
            id:       null,
            type:     $doc->type,
            number:   $data['number'],
            date:     $doc->date,
            status:   'draft',
            customer: $doc->customer,
            data:     $data,
        );
        return $this->create($copy);
    }
}
