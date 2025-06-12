<?php
require 'config.php';

// $submitted = false;
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
//     $stmt = $pdo->prepare("INSERT INTO queued_tweets (content, status, created_by) VALUES (?, 'pending', NULL)");
//     $stmt->execute([trim($_POST['content'])]);
//     $submitted = true;
// }

$submitted = isset($_GET['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $stmt = $pdo->prepare("INSERT INTO queued_tweets (content, status, created_by) VALUES (?, 'pending', NULL)");
    $stmt->execute([trim($_POST['content'])]);

    // Redirect after successful POST to avoid resubmission and fix toast
    header("Location: ?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>منصة تذكير بالاستغفار</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @keyframes fade-in {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    .animate-fade-in { animation: fade-in 0.8s ease-out forwards; }
    .spinner {
      border: 3px solid transparent;
      border-top: 3px solid white;
      border-radius: 9999px;
      width: 1rem;
      height: 1rem;
      animation: spin 0.6s linear infinite;
    }
    
  </style>
</head>
<body class="bg-gradient-to-tr from-gray-900 via-gray-800 to-gray-900 text-white font-sans min-h-screen p-4 sm:p-8 flex items-center justify-center">

  <div class="w-full max-w-2xl space-y-10 animate-fade-in">
    
    <!-- Header -->
    <div class="bg-gray-800 rounded-2xl p-6 sm:p-8 shadow-xl text-center space-y-5">
      <h1 class="text-3xl sm:text-4xl font-extrabold text-blue-400">تذكير بالاستغفار</h1>
      <p class="text-gray-300 text-lg leading-relaxed">
        يتم النشر التلقائي للتغريدات والأذكار كل ساعة على حساب <span class="text-white font-bold">@tdhkir11</span>.
        <br>يمكنك المشاركة بإرسال تغريدتك هنا.
      </p>
      <a href="https://twitter.com/tdhkir11" target="_blank"
         class="inline-block bg-blue-600 hover:bg-blue-700 transition px-5 py-2 rounded-full text-sm font-semibold shadow">
        زيارة الحساب على تويتر
      </a>
    </div>

    <!-- Form -->
    <div class="bg-gray-800 rounded-2xl p-6 sm:p-8 shadow-lg space-y-6">
      <h2 class="text-2xl font-semibold text-blue-300">📝 إرسال تغريدة جديدة</h2>
        <p class="text-gray-400 text-sm">
            يمكنك كتابة تغريدة قصيرة تتضمن ذكرًا أو دعاءً. سيتم مراجعة التغريدة قبل النشر.
   <?php if ($submitted): ?>
  <div id="success-toast" class="bg-green-600 text-white text-center py-2 rounded-lg animate-fade-in"
       style="transition: opacity 0.8s ease;">
    ✅ تم إرسال التغريدة بنجاح! سيتم مراجعتها قريباً.
  </div>
<?php endif; ?>


      <form method="POST" onsubmit="return handleSubmit()" class="space-y-4">
        <textarea name="content" rows="4" required
          class="w-full p-4 bg-gray-900 border border-gray-700 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="اكتب تغريدتك هنا..."></textarea>

        <button type="submit" id="submit-btn"
          class="w-full flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-xl transition-all">
          <span id="submit-text">إرسال</span>
          <div id="spinner" class="spinner hidden ml-2"></div>
        </button>
      </form>
    </div>

    <!-- Footer -->
    <div class="text-center text-gray-500 text-sm border-t border-gray-700 pt-6">
      تم بناء هذه المنصة لنشر الأذكار ومشاركة الخير.
      <br>
      <span class="text-gray-400">للتواصل:</span>
      <a href="mailto:azam.alkhodiriy@gmail.com" class="text-blue-400 hover:underline">
        azam.alkhodiriy@gmail.com
      </a>
    </div>

  </div>

  <script>
//   function handleSubmit() {
//     const btn = document.getElementById('submit-btn');
//     const text = document.getElementById('submit-text');
//     const spinner = document.getElementById('spinner');
//     btn.disabled = true;
//     spinner.classList.remove('hidden');
//     text.textContent = "جاري الإرسال...";
//     return true;
//   }

//   // Hide and remove success toast
//   window.addEventListener("DOMContentLoaded", () => {
//     const toast = document.getElementById("success-toast");
//     if (toast) {
//       setTimeout(() => {
//         toast.classList.add("opacity-0");
//         setTimeout(() => toast.remove(), 1000); // remove after fade-out
//       }, 4000);
//     }
//   });

  // Only runs if toast exists (means success=1 is in URL)
  document.addEventListener("DOMContentLoaded", () => {
    const toast = document.getElementById("success-toast");
    if (toast) {
      setTimeout(() => {
        toast.style.opacity = "0";
        setTimeout(() => toast.remove(), 1000);
      }, 4000);
    }
  });

  function handleSubmit() {
    const btn = document.getElementById('submit-btn');
    const text = document.getElementById('submit-text');
    const spinner = document.getElementById('spinner');
    btn.disabled = true;
    spinner.classList.remove('hidden');
    text.textContent = "جاري الإرسال...";
    return true;
  }



</script>

</body>
</html>
