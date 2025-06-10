<!-- views/login_form.php -->
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - لوحة التغريد</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4">
    <form action="/athkar/auth.php" method="POST" class="bg-white p-6 sm:p-8 rounded shadow-md w-full max-w-sm sm:max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">تسجيل الدخول</h2>

        <input type="text" name="username" placeholder="اسم المستخدم"
               class="w-full p-3 mb-4 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>

        <input type="password" name="password" placeholder="كلمة المرور"
               class="w-full p-3 mb-4 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded transition">دخول</button>
    </form>
</body>
</html>
