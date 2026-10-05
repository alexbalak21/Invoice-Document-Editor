<?php
/**
 * preview.php — Print-ready A4 document
 * Usage: preview.php?id=X
 */

define('DB_PATH', __DIR__ . '/db/documents.sqlite');

function getDoc(int $id): ?array {
    if (!file_exists(DB_PATH)) return null;
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT data FROM documents WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    return json_decode($row['data'], true);
}

$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doc = $id ? getDoc($id) : null;

if (!$doc) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;padding:40px">Document not found.</p>';
    exit;
}

$title = htmlspecialchars(($doc['type'] ?? 'DOCUMENT') . ' ' . ($doc['number'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <link rel="stylesheet" href="assets/document.css">
  <style>
    body { background: #ececec; margin: 0; }
    .page { margin: 24px auto 24px; }
    @media print {
      body { background: white; }
      .page { margin: 0; box-shadow: none; }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/templates/document.php'; ?>
</body>
</html>
