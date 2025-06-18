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

// Fetch all users
$stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <h2 class="text-xl font-bold mt-10 mb-4">جميع المستخدمين</h2>

<div class="overflow-x-auto w-full mt-6">
    <table class="min-w-full border bg-white rounded shadow text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2">الاسم</th>
                    <th class="p-2">البريد</th>
                    <th class="p-2">الدور</th>
                    <th class="p-2">تاريخ الإضافة</th>
                    <th class="p-2">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="border-t" id="user-row-<?= $user['id'] ?>">
                        <td class="p-2"><?= htmlspecialchars($user['username']) ?></td>
                        <td class="p-2"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="p-2">
                            <select onchange="updateRole(<?= $user['id'] ?>, this.value)" class="border rounded px-2 py-1 text-sm">
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="contributor" <?= $user['role'] === 'contributor' ? 'selected' : '' ?>>Contributor</option>
                            </select>
                        </td>
                        <td class="p-2"><?= $user['created_at'] ?></td>
                        <td class="p-2 text-center">
                            <button onclick="deleteUser(<?= $user['id'] ?>)" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs">
                                حذف
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

        
        <script>
function updateRole(userId, role) {
    fetch('actions/update_user_role.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${userId}&role=${role}`
    })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === "OK") {
            showToast("✅ تم تحديث الدور", "green");
        } else {
            showToast("❌ حدث خطأ أثناء التحديث", "red");
        }
    });
}

function deleteUser(userId) {
    if (!confirm("هل أنت متأكد أنك تريد حذف هذا المستخدم؟")) return;

    fetch('actions/delete_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${userId}`
    })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === "OK") {
            document.getElementById(`user-row-${userId}`).remove();
            showToast("🗑️ تم حذف المستخدم", "green");
        } else {
            showToast("❌ لم يتم الحذف", "red");
        }
    });
}

function showToast(message, color = "green") {
    let toast = document.getElementById("toast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "toast";
        toast.className = "fixed top-4 left-1/2 transform -translate-x-1/2 px-4 py-2 rounded shadow text-white text-sm z-50";
        document.body.appendChild(toast);
    }

    toast.className = `fixed top-4 left-1/2 transform -translate-x-1/2 px-4 py-2 rounded shadow text-white text-sm z-50 bg-${color}-600`;
    toast.textContent = message;
    toast.classList.remove("hidden");

    setTimeout(() => toast.classList.add("hidden"), 3000);
}
</script>

    </div>
</body>
</html>
