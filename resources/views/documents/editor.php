<?php
// Copy the editor HTML from old editor.php but with:
// - API URLs updated to new REST routes
// - PHP vars ($editId, $newType) already extracted by Response::view()
$logoPath   = ROOT . '/public/assets/logo.png';
$logoDataUri = '';
if (file_exists($logoPath)) {
    $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}
?>
<?php // ── Inline the full editor HTML from the old editor.php, with updated API paths ── ?>
<?php require ROOT . '/resources/views/partials/editor_body.php'; ?>
