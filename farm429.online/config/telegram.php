<?php
define('TELEGRAM_BOT_TOKEN', '8078224361:AAFKaPaVTm7yRGEe5jnXWIsPJxsI3LmmrhU');
define('TELEGRAM_BOT_USERNAME', 'FitoDomik_bot');
function sendTelegramMessage($chat_id, $message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result, true);
}
function sendAlarmNotification($user_id, $message) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT telegram_chat_id FROM users WHERE id = ? AND telegram_chat_id IS NOT NULL");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $user['telegram_chat_id']) {
            return sendTelegramMessage($user['telegram_chat_id'], $message);
        }
        return false;
    } catch (Exception $e) {
        error_log("Error sending Telegram notification: " . $e->getMessage());
        return false;
    }
} 