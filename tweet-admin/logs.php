<?php
require 'config.php'; // $pdo connection

$stmt = $pdo->query("SELECT * FROM logs ORDER BY log_time DESC LIMIT 1000");
$logs = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC)); // reverse to show top→bottom

// Optional: keyword emoji mapping
function format_log_message($message) {
    $message = htmlspecialchars($message);
    $map = [
        'Launching browser' => '🚀',
        'Trying login' => '🔐',
        'Logged in' => '✅',
        'Tweet posted successfully' => '📢',
        'Duplicate tweet' => '⚠️',
        'Trying to post' => '✍️',
        'Clicked Post' => '📤',
        'Telegram notification sent' => '📨',
        'Failed to post' => '❌',
        'Browser closed' => '🛑',
    ];
    foreach ($map as $keyword => $emoji) {
        if (str_contains($message, $keyword)) {
            return "<span class='font-bold'>$emoji</span> $message";
        }
    }
    return $message;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tweet Bot Logs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            height: 100%;
        }

        #log-container {
            word-break: break-word;
            white-space: normal;
        }
    </style>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function loadLogs() {
            fetch('logs_data.php')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('log-container').innerHTML = html;
                })
                .catch(error => {
                    console.error("Failed to load logs:", error);
                });
        }

        let logContainer = null;
        let lastScrollHeight = 0;
        let atBottom = true;

        function scrollToBottom() {
            logContainer.scrollTop = logContainer.scrollHeight;
            atBottom = true;
            document.getElementById('scroll-btn').classList.add('hidden');
            document.getElementById('new-log-alert').classList.add('hidden');
        }

        function checkScroll() {
            const threshold = 50;
            const isAtBottom = (logContainer.scrollTop + logContainer.clientHeight + threshold) >= logContainer.scrollHeight;
            atBottom = isAtBottom;

            // Show/hide scroll-to-bottom button
            const scrollBtn = document.getElementById('scroll-btn');
            if (!atBottom) {
                scrollBtn.classList.remove('hidden');
            } else {
                scrollBtn.classList.add('hidden');
            }
        }

        function loadLogs() {
            fetch('logs_data.php')
                .then(response => response.text())
                .then(html => {
                    const previousHeight = logContainer.scrollHeight;
                    logContainer.innerHTML = html;

                    // Show alert if new logs and user is not at bottom
                    const newHeight = logContainer.scrollHeight;
                    if (!atBottom && newHeight > previousHeight) {
                        document.getElementById('new-log-alert').classList.remove('hidden');
                    }

                    if (atBottom) scrollToBottom();
                })
                .catch(error => {
                    console.error("Failed to load logs:", error);
                });
        }

        // Initial load
        window.addEventListener('DOMContentLoaded', () => {
            logContainer = document.getElementById('log-container');
            loadLogs();
            setInterval(loadLogs, 5000); // refresh every 10 seconds
            scrollToBottom();

            // Detect scroll position
            logContainer.addEventListener('scroll', checkScroll);
        });
    </script>
</head>
<body class="bg-gradient-to-b from-gray-900 to-gray-800 text-gray-100 min-h-screen flex flex-col">
    <div class="flex-grow w-full max-w-5xl mx-auto bg-gray-950 rounded-xl shadow-xl p-4 sm:p-6 flex flex-col">
        <h1 class="text-2xl font-bold mb-4 text-center text-white">🚀 Tweet Bot Activity Log</h1>

        <div id="log-container"
             class="flex-grow overflow-y-auto max-h-[75vh] space-y-3 text-sm font-mono leading-relaxed scroll-smooth p-2 sm:p-4 bg-gray-900 rounded">
            <div>Loading logs...</div>
        </div>

            <!-- Scroll-to-bottom button -->
            <button id="scroll-btn"
                    onclick="scrollToBottom()"
                    class="hidden fixed bottom-4 right-4 sm:bottom-8 sm:right-8 bg-blue-600 text-white text-sm px-3 py-2 sm:px-4 sm:py-2 rounded shadow hover:bg-blue-700 transition z-50">
                Scroll to Bottom
            </button>

            <!-- New log notification -->
            <div id="new-log-alert"
                onclick="scrollToBottom()"
                class="hidden fixed top-4 left-1/2 transform -translate-x-1/2 bg-yellow-500 text-black font-semibold text-sm px-4 py-2 rounded shadow cursor-pointer z-50">
                🔔 New logs-tap to view
            </div>

              <div class="text-center mt-4 text-gray-400 text-xs">
            Showing live logs — auto-refreshes every 10 seconds
        </div>
    </div>
</body>
