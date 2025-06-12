<!-- auth.php -->
<?php
// Extend session lifetime to 7 days
ini_set('session.gc_maxlifetime', 7776000); // 7 days in seconds
session_set_cookie_params([
    'lifetime' => 7776000,  // 7 days
    'path' => '/',
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']), // auto set secure if HTTPS
    'samesite' => 'Lax'
]);
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


