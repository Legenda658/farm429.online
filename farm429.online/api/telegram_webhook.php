<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/telegram.php';
$log_file = $_SERVER['DOCUMENT_ROOT'] . '/logs/telegram_webhook.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Получен запрос\n", FILE_APPEND);
$update = json_decode(file_get_contents('php://input'), true);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Данные: " . json_encode($update) . "\n", FILE_APPEND);
if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $username = $message['from']['username'] ?? null;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Обработка сообщения: chat_id=$chat_id, text=$text, username=$username\n", FILE_APPEND);
    if ($text === '/start') {
        $response = "Добро пожаловать в ФитоДомик! Для верификации вашего аккаунта, пожалуйста, введите ваш никнейм с сайта.";
        sendTelegramMessage($chat_id, $response);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Отправлен ответ на /start\n", FILE_APPEND);
        return;
    }
    $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
    $stmt->execute([$text]);
    if ($stmt->fetch()) {
        sendTelegramMessage($chat_id, "Этот никнейм уже зарегистрирован.");
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Никнейм уже зарегистрирован\n", FILE_APPEND);
        return;
    }
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'telegram_verifications'");
        if ($tableCheck->rowCount() == 0) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Создаем таблицу telegram_verifications, она не существует\n", FILE_APPEND);
            $pdo->exec("CREATE TABLE `telegram_verifications` (
                `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `username` varchar(255) NOT NULL,
                `telegram_username` varchar(255) NOT NULL,
                `telegram_chat_id` varchar(255) NOT NULL,
                `is_verified` tinyint(1) DEFAULT 1,
                `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        } else {
            try {
                $checkColumns = $pdo->query("DESCRIBE telegram_verifications");
                $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
                $requiredColumns = ['id', 'username', 'telegram_username', 'telegram_chat_id', 'is_verified', 'created_at'];
                $missingColumns = array_diff($requiredColumns, $columns);
                if (!empty($missingColumns)) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Таблица существует, но с неправильной структурой. Отсутствуют колонки: " . implode(', ', $missingColumns) . "\n", FILE_APPEND);
                    $pdo->exec("DROP TABLE `telegram_verifications`");
                    $pdo->exec("CREATE TABLE `telegram_verifications` (
                        `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        `username` varchar(255) NOT NULL,
                        `telegram_username` varchar(255) NOT NULL,
                        `telegram_chat_id` varchar(255) NOT NULL,
                        `is_verified` tinyint(1) DEFAULT 1,
                        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Пересоздана таблица telegram_verifications с правильной структурой\n", FILE_APPEND);
                }
            } catch (PDOException $structureError) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Ошибка при проверке структуры таблицы: " . $structureError->getMessage() . "\n", FILE_APPEND);
            }
        }
        $pdo->exec("SET SESSION SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
        $check = $pdo->prepare("SELECT id FROM telegram_verifications WHERE username = ?");
        $check->execute([$text]);
        $existingRecord = $check->fetch(PDO::FETCH_ASSOC);
        if ($existingRecord) {
            $stmt = $pdo->prepare("UPDATE telegram_verifications SET 
                telegram_username = ?,
                telegram_chat_id = ?,
                is_verified = 1
                WHERE username = ?");
            $result = $stmt->execute([$username, $chat_id, $text]);
            if ($result) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Обновлена существующая запись ID:" . $existingRecord['id'] . " для username=$text\n", FILE_APPEND);
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Ошибка при обновлении записи: " . json_encode($stmt->errorInfo()) . "\n", FILE_APPEND);
                throw new PDOException("Ошибка при обновлении записи: " . json_encode($stmt->errorInfo()));
            }
        } else {
            $nextIdStmt = $pdo->query("SELECT IFNULL(MAX(id), 0) + 1 as next_id FROM users");
            $nextId = $nextIdStmt->fetch(PDO::FETCH_ASSOC)['next_id'];
            if ($nextId <= 0) {
                $nextId = 1;
            }
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Используем ID из таблицы users: " . $nextId . " для новой верификации\n", FILE_APPEND);
            $stmt = $pdo->prepare("INSERT INTO telegram_verifications 
                (id, username, telegram_username, telegram_chat_id) 
                VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$nextId, $text, $username, $chat_id]);
            if ($result) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Создана новая запись ID:" . $pdo->lastInsertId() . " для username=$text\n", FILE_APPEND);
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Ошибка при создании записи: " . json_encode($stmt->errorInfo()) . "\n", FILE_APPEND);
                throw new PDOException("Ошибка при создании записи: " . json_encode($stmt->errorInfo()));
            }
        }
        sendTelegramMessage($chat_id, "Успешная верификация! Теперь вы можете вернуться на сайт.");
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Верификация успешна для username=$text\n", FILE_APPEND);
    } catch (PDOException $e) {
        sendTelegramMessage($chat_id, "Произошла ошибка при верификации. Пожалуйста, попробуйте позже.");
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Ошибка верификации для username=$text: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}