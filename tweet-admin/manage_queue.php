<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: views/login_form.php");
    exit;
}
require 'config.php';

$userId = $_SESSION['user_id'] ?? 1;
$username = $_SESSION['user'];
$role = $_SESSION['role'] ?? 'contributor';

// Add new queued tweet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $stmt = $pdo->prepare("INSERT INTO queued_tweets (content, status, created_by) VALUES (?, 'pending', ?)");
    $stmt->execute([$_POST['content'], $userId]);
    header("Location: manage_queue.php");
    exit;
}

// Delete tweet
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM queued_tweets WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_queue.php");
    exit;
}

// Fetch tweets
$stmt = $pdo->query("SELECT q.*, u.username FROM queued_tweets q LEFT JOIN users u ON q.created_by = u.id ORDER BY q.created_at DESC");
$tweets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Queued Tweets</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-4 md:p-8">
    <div class="max-w-3xl mx-auto bg-white p-4 md:p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4 text-center md:text-left">Manage Queued Tweets</h1>

        <form method="POST" class="mb-6">
            <textarea name="content" class="w-full p-2 border rounded mb-2" placeholder="Enter new queued tweet..." required></textarea>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded w-full md:w-auto">Add Tweet</button>
        </form>

        <div class="space-y-4">
            <?php foreach ($tweets as $row): ?>
                <?php
                    $status = $row['status'];
                    $badgeColor = match($status) {
                        'posted' => 'bg-green-100 text-green-700',
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        default => 'bg-gray-200 text-gray-800'
                    };
                ?>
                <div class="p-4 border rounded bg-gray-50 flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div class="mb-2 md:mb-0">
                        <div class="mb-1"><?= htmlspecialchars($row['content']) ?></div>
                        <div class="text-sm text-gray-500">By <?= htmlspecialchars($row['username'] ?? 'Unknown') ?> — <?= $row['created_at'] ?></div>
                    </div>
                    <div class="flex items-center space-x-3 mt-2 md:mt-0">
                        <span class="px-2 py-1 text-sm rounded <?= $badgeColor ?>"><?= ucfirst($status) ?></span>
                         <?php if ($role === 'admin'): ?>
                        <a href="?delete=<?= $row['id'] ?>" class="text-red-600 hover:underline text-sm">Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 text-center md:text-left">
            <a href="dashboard.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
