<?php
require '../config.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit("Forbidden");
}

$id = intval($_POST['id'] ?? 0);
$role = $_POST['role'] ?? '';

if (!in_array($role, ['admin', 'contributor'])) {
    exit("Invalid role");
}

$stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->execute([$role, $id]);
echo "OK";
