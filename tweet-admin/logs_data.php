<?php
require 'config.php';

$stmt = $pdo->query("SELECT * FROM logs ORDER BY log_time DESC LIMIT 100");
$logs = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC)); // newest at bottom

function format_log_message($message) {
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
            return "$emoji " . htmlspecialchars($message);
        }
    }
    return htmlspecialchars($message);
}

foreach ($logs as $log) {
    $border = str_contains($log['message'], 'Failed') ? 'border-red-500' :
              (str_contains($log['message'], 'Duplicate') ? 'border-yellow-400' : 'border-green-500');

    echo "<div class='bg-gray-800 hover:bg-gray-700 transition rounded px-4 py-2 border-l-4 $border'>";
    echo "<span class='text-gray-400'>{$log['log_time']}</span><br>";
    echo "<span>" . format_log_message($log['message']) . "</span>";
    echo "</div>";
}
?>
