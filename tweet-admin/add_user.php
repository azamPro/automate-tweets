<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: views/login_form.php");
    exit;
}
require 'config.php';

$error = $success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = hash('sha256', $_POST['password']);
    $role = $_POST['role'];

    if ($username && $password && in_array($role, ['admin', 'contributor'])) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $password, $role])) {
            $success = "User added successfully!";
        } else {
            $error = "Failed to add user.";
        }
    } else {
        $error = "Please fill all fields correctly.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-4 sm:p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Add New User</h1>
            <a href="dashboard.php" class="text-blue-600 hover:underline text-sm">← Back to Dashboard</a>
        </div>


        <?php if ($success): ?>
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="text" name="username" placeholder="Username" class="w-full border p-2 rounded" required>
            <input type="email" name="email" placeholder="Email (optional)" class="w-full border p-2 rounded">
            <input type="password" name="password" placeholder="Password" class="w-full border p-2 rounded" required>

            <select name="role" class="w-full border p-2 rounded" required>
                <option value="contributor">Contributor</option>
                <option value="admin">Admin</option>
            </select>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full">Create User</button>
        </form>

        
    </div>
</body>
</html>
