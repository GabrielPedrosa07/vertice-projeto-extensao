<?php
require_once __DIR__ . '/../app/Models/Database.php';
$db = App\Models\Database::getConnection();

$stmt = $db->query("
    SELECT id, caller_id, receiver_id, status, created_at, 
           TIMESTAMPDIFF(SECOND, created_at, NOW()) as diff_seconds
    FROM private_call_signals
    ORDER BY id DESC LIMIT 3
");
print_r($stmt->fetchAll());
