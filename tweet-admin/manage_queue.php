<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: views/login_form.php");
    exit;
}
require 'config.php';

$filterStatus = $_GET['status'] ?? '';
$filterApproval = $_GET['approved'] ?? '';

$whereClauses = [];
$params = [];

if ($filterStatus !== '') {
    $whereClauses[] = 'q.status = ?';
    $params[] = $filterStatus;
}

if ($filterApproval !== '') {
    if ($filterApproval === 'pending') {
        $whereClauses[] = 'q.approved IS NULL';
    } else {
        $whereClauses[] = 'q.approved = ?';
        $params[] = (int)$filterApproval;
    }
}

$whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$stmt = $pdo->prepare("SELECT q.*, u.username FROM queued_tweets q LEFT JOIN users u ON q.created_by = u.id $whereSQL ORDER BY q.created_at DESC");
$stmt->execute($params);
$tweets = $stmt->fetchAll();


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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['change_status_id']) && isset($_POST['new_status'])) {
        $id = intval($_POST['change_status_id']);
        $newStatus = $_POST['new_status'] === 'pending' ? null : intval($_POST['new_status']);
        $stmt = $pdo->prepare("UPDATE queued_tweets SET approved = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
    }


    header("Location: manage_queue.php");
    exit;
}
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
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Manage Queued Tweets</h1>
            <a href="dashboard.php" class="text-blue-600 hover:underline text-sm">← Back to Dashboard</a>
        </div>

        <form method="POST" class="mb-6">
            <textarea name="content" class="w-full p-2 border rounded mb-2" placeholder="Enter new queued tweet..." required></textarea>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded w-full md:w-auto">Add Tweet</button>
        </form>

        <form method="GET" class="mb-6 flex flex-wrap gap-4 items-center">
            <label class="text-sm">Status:
                <select name="status" onchange="this.form.submit()" class="ml-1 px-2 py-1 border rounded">
                    <option value="">All</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="posted" <?= $filterStatus === 'posted' ? 'selected' : '' ?>>Posted</option>
                </select>
            </label>

            <label class="text-sm">Approval:
                <select name="approved" onchange="this.form.submit()" class="ml-1 px-2 py-1 border rounded">
                    <option value="">All</option>
                    <option value="pending" <?= $filterApproval === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                    <option value="1" <?= $filterApproval === '1' ? 'selected' : '' ?>>✅ Approved</option>
                    <option value="0" <?= $filterApproval === '0' ? 'selected' : '' ?>>❌ Rejected</option>
                </select>
            </label>
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
                <div class="p-4 border rounded bg-gray-50 space-y-2">
                    <div>
                        <div class="mb-1 font-medium text-gray-800"><?= htmlspecialchars($row['content']) ?></div>
                        <div class="text-sm text-gray-500">By <?= htmlspecialchars($row['username'] ?? 'Unknown') ?> — <?= $row['created_at'] ?></div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-1 text-sm rounded <?= $badgeColor ?>"><?= ucfirst($status) ?></span>

                        <?php ?>
                            <div class="flex gap-2 flex-wrap">
                                
                                    
                                    <select onchange="updateApproval(this, <?= $row['id'] ?>)"
                                        class="px-2 py-1 text-sm rounded border bg-white text-gray-800">
                                    <option value="pending" <?= is_null($row['approved']) ? 'selected' : '' ?>>⏳ Pending</option>
                                    <option value="1" <?= $row['approved'] === '1' || $row['approved'] === 1 ? 'selected' : '' ?>>✅ Approved</option>
                                    <option value="0" <?= $row['approved'] === '0' || $row['approved'] === 0 ? 'selected' : '' ?>>❌ Rejected</option>
                                </select>

                                <form method="POST">
                                    <input type="hidden" name="delete" value="<?= $row['id'] ?>">
                                    <button type="submit"
                                        class="bg-red-100 text-red-700 text-sm px-2 py-1 rounded <?= $role !== 'admin' ? 'opacity-50 cursor-not-allowed' : 'hover:bg-red-200' ?>"
                                        <?= $role !== 'admin' ? 'disabled' : '' ?>>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        
    </div>

    <div id="toast" class="hidden"></div>

    <script>
    function showToast(message, color = "green") {
        const toast = document.getElementById('toast');
        toast.textContent = message;

        toast.className = `fixed top-4 left-1/2 transform -translate-x-1/2 text-white px-4 py-2 rounded shadow z-50 text-sm bg-${color}-500`;
        
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    }


function updateApproval(select, id) {
    const status = select.value;

    fetch('actions/update_approval.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&status=${status}`
    })
    .then(response => {
        if (!response.ok) throw new Error("Request failed");
        return response.text();
    })
    .then(responseText => {
        if (responseText.trim() === "OK") {
            showToast("✅ Approval updated", "green");
        } else {
            throw new Error(responseText);
        }
    })
    .catch(error => {
        console.error(error);
        showToast("❌ Failed to update approval", "red");
    });
}

    </script>

</body>
</html>
