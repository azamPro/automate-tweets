<!-- login.php -->
<?php
session_start();
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <form method="POST" class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-xl font-bold mb-6 text-center">Admin Login</h2>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>
        <input name="username" placeholder="Username" class="w-full mb-4 p-2 border rounded" required>
        <input name="password" type="password" placeholder="Password" class="w-full mb-4 p-2 border rounded" required>
        <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Login</button>
    </form>
</body>
</html>
