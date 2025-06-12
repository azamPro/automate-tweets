<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: views/login_form.php");
    exit;
}
require 'config.php';

$username = $_SESSION['user'];
$role = $_SESSION['role'] ?? 'contributor';
$userId = $_SESSION['user_id'] ?? 1;

// Add new static tweet (admin only)
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $stmt = $pdo->prepare("INSERT INTO static_tweets (content, created_by) VALUES (?, ?)");
    $stmt->execute([$_POST['content'], $userId]);
    header("Location: manage_static.php");
    exit;
}

// Delete static tweet (admin only)
if ($role === 'admin' && isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM static_tweets WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_static.php");
    exit;
}

// Fetch all static tweets
$stmt = $pdo->query("SELECT s.*, u.username FROM static_tweets s LEFT JOIN users u ON s.created_by = u.id ORDER BY s.created_at DESC");
$tweets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Static Tweets</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-4 md:p-8">
    <div class="max-w-3xl mx-auto bg-white p-4 md:p-6 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Manage Static Tweets</h1>
            <a href="dashboard.php" class="text-blue-600 hover:underline text-sm">← Back to Dashboard</a>
        </div>

        <?php if ($role === 'admin'): ?>
        <form method="POST" class="mb-6">
            <textarea name="content" class="w-full p-2 border rounded mb-2" placeholder="Enter new static tweet..." required></textarea>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full md:w-auto">Add Tweet</button>
        </form>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($tweets as $row): ?>
                <div class="p-4 border rounded bg-gray-50 flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div class="mb-2 md:mb-0">
                        <div><?= htmlspecialchars($row['content']) ?></div>
                        <div class="text-sm text-gray-500">By <?= htmlspecialchars($row['username'] ?? 'Unknown') ?> — <?= $row['created_at'] ?></div>
                    </div>
                    <?php if ($role === 'admin'): ?>
                        <a href="?delete=<?= $row['id'] ?>" class="text-red-600 hover:underline text-sm mt-2 md:mt-0">Delete</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</body>
</html>
