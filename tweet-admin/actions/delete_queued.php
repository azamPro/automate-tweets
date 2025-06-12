<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM queued_tweets WHERE id = ?");
    $stmt->execute([$id]);
    echo "OK";
} else {
    http_response_code(400);
    echo "Invalid ID";
}
