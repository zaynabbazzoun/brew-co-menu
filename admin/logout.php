<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Invalid logout request.');
}

endAdminSession();
header('Location: login.php?logged_out=1');
exit;
