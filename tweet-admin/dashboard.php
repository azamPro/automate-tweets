<!-- dashboard.php -->
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: views/login_form.php");
    exit;
}
$username = is_array($_SESSION['user']) ? ($_SESSION['user']['username'] ?? 'Unknown') : $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-4 sm:p-8">
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-row justify-between items-start sm:items-center mb-6 gap-3">
            <h1 class="text-xl sm:text-2xl font-bold">Welcome, <?= htmlspecialchars($username) ?>!</h1>
            <a href="logout.php"
            class="inline-block bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded transition self-end sm:self-auto">
            Logout
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="manage_static.php" class="bg-white p-5 rounded-lg shadow hover:bg-blue-50 transition">
                <h2 class="text-lg sm:text-xl font-semibold">Manage Static Tweets</h2>
                <p class="text-gray-600 mt-1">Reusable tweets with random styling.</p>
            </a>
            <a href="manage_queue.php" class="bg-white p-5 rounded-lg shadow hover:bg-green-50 transition">
                <h2 class="text-lg sm:text-xl font-semibold">Manage Queued Tweets</h2>
                <p class="text-gray-600 mt-1">Will be posted once and archived.</p>
            </a>
        </div>
    </div>
</body>
</html>
