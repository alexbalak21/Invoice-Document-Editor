<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars(($doc['type'] ?? 'DOCUMENT') . ' ' . ($doc['number'] ?? '')) ?></title>
  <link rel="stylesheet" href="/assets/document.css">
  <style>
    body { background: #ececec; margin: 0; }
    .page { margin: 24px auto; }
    @media print { body { background: white; } .page { margin: 0; box-shadow: none; } }
  </style>
</head>
<body>
<?php require ROOT . '/resources/views/partials/document_render.php'; ?>
</body>
</html>
