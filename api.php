<?php
/**
 * api.php — Simple JSON API for invoice/quote storage
 * All responses are JSON. All writes use PDO + SQLite.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

define('DB_PATH', __DIR__ . '/db/documents.sqlite');

// ── Bootstrap DB ──────────────────────────────────────────────
function getDB(): PDO {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS documents (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            type        TEXT NOT NULL DEFAULT 'INVOICE',
            number      TEXT NOT NULL DEFAULT '',
            date        TEXT NOT NULL DEFAULT '',
            status      TEXT NOT NULL DEFAULT 'draft',
            customer    TEXT NOT NULL DEFAULT '',
            data        TEXT NOT NULL DEFAULT '{}',
            created_at  TEXT DEFAULT (datetime('now')),
            updated_at  TEXT DEFAULT (datetime('now'))
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customers (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT NOT NULL DEFAULT '',
            address    TEXT NOT NULL DEFAULT '',
            city       TEXT NOT NULL DEFAULT '',
            contact    TEXT NOT NULL DEFAULT '',
            phone      TEXT NOT NULL DEFAULT '',
            vat        TEXT NOT NULL DEFAULT '',
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        )
    ");
    return $pdo;
}

// ── Upsert customer from document data (called on save/update) ─
function upsertCustomer(PDO $pdo, array $cust): void {
    $name = trim($cust['name'] ?? '');
    if ($name === '') return;
    $existing = $pdo->prepare("SELECT id FROM customers WHERE name = ? COLLATE NOCASE LIMIT 1");
    $existing->execute([$name]);
    $row = $existing->fetch();
    if ($row) {
        $pdo->prepare("
            UPDATE customers SET address=?, city=?, contact=?, phone=?, vat=?, updated_at=datetime('now')
            WHERE id=?
        ")->execute([
            trim($cust['address'] ?? ''), trim($cust['city'] ?? ''),
            trim($cust['contact'] ?? ''), trim($cust['phone'] ?? ''),
            trim($cust['vat'] ?? ''), $row['id'],
        ]);
    } else {
        $pdo->prepare("
            INSERT INTO customers (name, address, city, contact, phone, vat)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $name, trim($cust['address'] ?? ''), trim($cust['city'] ?? ''),
            trim($cust['contact'] ?? ''), trim($cust['phone'] ?? ''),
            trim($cust['vat'] ?? ''),
        ]);
    }
}

function respond(array $payload, int $code = 200): never {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function error(string $msg, int $code = 400): never {
    respond(['ok' => false, 'error' => $msg], $code);
}

// ── Default document template ─────────────────────────────────
function defaultDocument(string $type = 'INVOICE'): array {
    $today = date('Y-m-d');
    return [
        'type'         => $type,
        'number'       => '',
        'date'         => $today,
        'due_date'     => '',
        'service_date' => '',
        'quote_ref'    => '',
        'tracking'     => '',

        'issuer' => [
            'name'         => 'NOVOCIB SAS',
            'address'      => 'BD de Chatillon, Quai Jean Voisin',
            'city'         => '62200 Boulogne-sur-Mer — France',
            'email'        => 'lbalakireva@novocib.com',
            'legal'        => "SAS, société par actions simplifiée — Share capital: 260 158,00 €\nEORI# FR48237937700047\nVAT# FR90 482 379 377",
            'logo_base64'  => '',
        ],

        'customer' => [
            'name'    => '',
            'address' => '',
            'city'    => '',
            'contact' => '',
            'phone'   => '',
            'vat'     => '',
        ],

        'currency'        => 'EUR',
        'currency_symbol' => '€',

        'items' => [],

        'vat_rate'         => 0,
        'amount_paid'      => 0,
        'show_amount_paid' => false,
        'balance_label'    => 'TOTAL DUE',

        'vat_mention'  => '',
        'notes'        => '',
        'terms'        => 'PAYMENT IMMEDIATE UPON RECEIPT, by credit card or wire transfer. A fixed indemnity of 40 euros for recovery costs is due to the creditor in case of late payment.',

        'bank' => [
            'label'        => 'Bank Details EUR - Banque Populaire, France',
            'beneficiary'  => 'SAS NOVOCIB',
            'bank_name'    => 'BANQUE POPULAIRE AUVERGNE RHÔNE ALPES (BPAURA)',
            'bank_address' => '215 Avenue Jean Jaurès, 69007 Lyon, France',
            'iban'         => 'FR76 1680 7004 0081 0876 0421 151',
            'bic'          => 'CCBPFRPPGRE',
        ],

        'footer_thanks'  => 'Thank you for your business!',
        'footer_contact' => "If you have any questions regarding this invoice, please contact us.\n• lbalakireva@novocib.com",
    ];
}

// ── Router ────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    // LIST
    if ($action === 'list' && $method === 'GET') {
        $q = trim($_GET['q'] ?? '');
        $filterType = trim($_GET['type'] ?? '');

        $sql = "SELECT id, type, number, date, status, customer, created_at, updated_at FROM documents WHERE 1=1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (number LIKE :q OR customer LIKE :q)";
            $params[':q'] = "%$q%";
        }
        if ($filterType !== '') {
            $sql .= " AND type = :type";
            $params[':type'] = $filterType;
        }
        $sql .= " ORDER BY updated_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond(['ok' => true, 'documents' => $stmt->fetchAll()]);
    }

    // GET ONE
    if ($action === 'get' && $method === 'GET') {
        if (!$id) error('Missing id');
        $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) error('Document not found', 404);
        $row['data'] = json_decode($row['data'], true);
        respond(['ok' => true, 'document' => $row]);
    }

    // CREATE
    if ($action === 'save' && $method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $data = $body['data'] ?? defaultDocument($body['type'] ?? 'INVOICE');
        $type     = strtoupper(trim($data['type'] ?? 'INVOICE'));
        $number   = trim($data['number'] ?? '');
        $date     = trim($data['date'] ?? date('Y-m-d'));
        $customer = trim($data['customer']['name'] ?? '');

        $stmt = $pdo->prepare("
            INSERT INTO documents (type, number, date, status, customer, data)
            VALUES (?, ?, ?, 'draft', ?, ?)
        ");
        $stmt->execute([$type, $number, $date, $customer, json_encode($data, JSON_UNESCAPED_UNICODE)]);
        $newId = $pdo->lastInsertId();
        upsertCustomer($pdo, $data['customer'] ?? []);
        respond(['ok' => true, 'id' => $newId]);
    }

    // UPDATE
    if ($action === 'update' && $method === 'POST') {
        if (!$id) error('Missing id');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $data = $body['data'] ?? [];
        if (empty($data)) error('No data provided');

        $type     = strtoupper(trim($data['type'] ?? 'INVOICE'));
        $number   = trim($data['number'] ?? '');
        $date     = trim($data['date'] ?? date('Y-m-d'));
        $status   = trim($body['status'] ?? 'draft');
        $customer = trim($data['customer']['name'] ?? '');

        $stmt = $pdo->prepare("
            UPDATE documents
            SET type=?, number=?, date=?, status=?, customer=?, data=?, updated_at=datetime('now')
            WHERE id=?
        ");
        $stmt->execute([$type, $number, $date, $status, $customer, json_encode($data, JSON_UNESCAPED_UNICODE), $id]);
        upsertCustomer($pdo, $data['customer'] ?? []);
        respond(['ok' => true, 'id' => $id]);
    }

    // DUPLICATE
    if ($action === 'duplicate' && $method === 'POST') {
        if (!$id) error('Missing id');
        $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) error('Document not found', 404);

        $data = json_decode($row['data'], true);
        $data['number'] = $data['number'] . '-COPY';

        $stmt2 = $pdo->prepare("
            INSERT INTO documents (type, number, date, status, customer, data)
            VALUES (?, ?, ?, 'draft', ?, ?)
        ");
        $stmt2->execute([$row['type'], $data['number'], $row['date'], $row['customer'], json_encode($data, JSON_UNESCAPED_UNICODE)]);
        respond(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    // DELETE
    if ($action === 'delete' && $method === 'POST') {
        if (!$id) error('Missing id');
        $pdo->prepare("DELETE FROM documents WHERE id = ?")->execute([$id]);
        respond(['ok' => true]);
    }

    // DEFAULT TEMPLATE
    if ($action === 'default' && $method === 'GET') {
        $type = strtoupper(trim($_GET['type'] ?? 'INVOICE'));
        respond(['ok' => true, 'data' => defaultDocument($type)]);
    }

    // ── CUSTOMERS ─────────────────────────────────────────────

    // LIST customers
    if ($action === 'customers' && $method === 'GET') {
        $q = trim($_GET['q'] ?? '');
        $sql = "SELECT * FROM customers WHERE 1=1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (name LIKE :q OR city LIKE :q OR contact LIKE :q)";
            $params[':q'] = "%$q%";
        }
        $sql .= " ORDER BY name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond(['ok' => true, 'customers' => $stmt->fetchAll()]);
    }

    // GET one customer
    if ($action === 'customer_get' && $method === 'GET') {
        if (!$id) error('Missing id');
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) error('Customer not found', 404);
        respond(['ok' => true, 'customer' => $row]);
    }

    // CREATE customer
    if ($action === 'customer_save' && $method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($body['name'] ?? '');
        if ($name === '') error('Name is required');
        $pdo->prepare("
            INSERT INTO customers (name, address, city, contact, phone, vat)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $name, trim($body['address'] ?? ''), trim($body['city'] ?? ''),
            trim($body['contact'] ?? ''), trim($body['phone'] ?? ''),
            trim($body['vat'] ?? ''),
        ]);
        respond(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    // UPDATE customer
    if ($action === 'customer_update' && $method === 'POST') {
        if (!$id) error('Missing id');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($body['name'] ?? '');
        if ($name === '') error('Name is required');
        $pdo->prepare("
            UPDATE customers SET name=?, address=?, city=?, contact=?, phone=?, vat=?, updated_at=datetime('now')
            WHERE id=?
        ")->execute([
            $name, trim($body['address'] ?? ''), trim($body['city'] ?? ''),
            trim($body['contact'] ?? ''), trim($body['phone'] ?? ''),
            trim($body['vat'] ?? ''), $id,
        ]);
        respond(['ok' => true, 'id' => $id]);
    }

    // DELETE customer
    if ($action === 'customer_delete' && $method === 'POST') {
        if (!$id) error('Missing id');
        $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        respond(['ok' => true]);
    }

    error('Unknown action or method', 404);

} catch (Throwable $e) {
    error('Server error: ' . $e->getMessage(), 500);
}