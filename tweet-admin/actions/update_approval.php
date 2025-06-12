<?php
require '../config.php';
session_start();

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo "Unauthorized.";
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

$newStatus = $status === 'pending' ? null : (int)$status;

$stmt = $pdo->prepare("UPDATE queued_tweets SET approved = ? WHERE id = ?");
$stmt->execute([$newStatus, $id]);

header('Content-Type: text/plain');
echo "OK";
exit;
