<?php
/**
 * Documentation view — the full content lives in the legacy documentation.php file.
 * We include it here as a transitional approach.
 * During refactor, move the HTML content of documentation.php body into this file.
 */

// Temporarily serve the old file directly until fully migrated
$legacyFile = ROOT . '/legacy/documentation_body.php';
if (is_file($legacyFile)) {
    include $legacyFile;
} else {
    echo '<div class="history-layout" style="padding:40px"><h1>Documentation</h1>
    <p style="color:#5a6070">Move <code>legacy/documentation_body.php</code> here to complete the migration.</p></div>';
}
