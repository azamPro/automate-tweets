<!-- auth.php -->
<?php
session_start();
include 'config.php';

$username = $_POST['username'];
$password = hash('sha256', $_POST['password']);

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->execute([$username, $password]);
$user = $stmt->fetch();

if ($user) {
    $_SESSION['user'] = $user['username'];
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    header("Location: dashboard.php");
} else {
    echo "<script>alert('Invalid login');window.location='index.php';</script>";
}
?>


