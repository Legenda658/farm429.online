<?php
require_once '../config/database.php';
require_once '../config/session.php';
header('Content-Type: application/json');
$log_file = $_SERVER['DOCUMENT_ROOT'] . '/logs/verification_log.txt';
function log_message($message) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}
log_message("Начало проверки верификации");
if (!isset($_SESSION['temp_user'])) {
    log_message("Ошибка: временные данные пользователя не найдены");
    echo json_encode(['verified' => false, 'message' => 'Временные данные пользователя не найдены']);
    exit;
}
$temp_user = $_SESSION['temp_user'];
$username = $temp_user['username'];
log_message("Проверка верификации для пользователя: " . $username);
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'telegram_verifications'");
    if ($checkTable->rowCount() == 0) {
        log_message("Таблица telegram_verifications не существует");
        echo json_encode(['verified' => false, 'message' => 'Таблица верификации не существует']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM telegram_verifications WHERE username = ?");
    $stmt->execute([$username]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);
    log_message("Результат запроса верификации: " . ($verification ? "Найдена запись" : "Запись не найдена"));
    if ($verification) {
        log_message("Верификация найдена: " . json_encode($verification));
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkUser->execute([$username]);
        if ($checkUser->fetch()) {
            log_message("Ошибка: пользователь с таким именем уже существует");
            echo json_encode(['verified' => false, 'message' => 'Пользователь с таким именем уже существует']);
            exit;
        }
        $userId = $verification['id'];
        log_message("Используем ID из верификации: " . $userId);
        try {
            $insertQuery = "INSERT INTO users (id, username, password, first_name, last_name, telegram_username, telegram_chat_id, is_verified) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            log_message("Выполняем запрос: " . $insertQuery);
            $stmt = $pdo->prepare($insertQuery);
            $result = $stmt->execute([
                $userId,
                $temp_user['username'],
                $temp_user['password'],
                $temp_user['first_name'],
                $temp_user['last_name'],
                $verification['telegram_username'],
                $verification['telegram_chat_id']
            ]);
            if (!$result) {
                log_message("Ошибка выполнения запроса: " . json_encode($stmt->errorInfo()));
                echo json_encode(['verified' => false, 'message' => 'Ошибка создания пользователя']);
                exit;
            }
            log_message("Создан новый пользователь с ID: " . $userId);
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $temp_user['username'];
            $_SESSION['first_name'] = $temp_user['first_name'];
            $_SESSION['last_name'] = $temp_user['last_name'];
            $_SESSION['telegram'] = $verification['telegram_username'];
            unset($_SESSION['temp_user']);
            log_message("Временные данные очищены, пользователь авторизован");
            echo json_encode(['verified' => true, 'message' => 'Верификация успешна']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                log_message("Ошибка дублирования ключа: " . $e->getMessage());
                $getNewId = $pdo->query("SELECT MAX(id) + 1 as new_id FROM users");
                $newId = $getNewId->fetch(PDO::FETCH_ASSOC)['new_id'];
                if ($newId <= 0) {
                    $newId = 1;
                }
                log_message("Пробуем с новым ID: " . $newId);
                $updateVerification = $pdo->prepare("UPDATE telegram_verifications SET id = ? WHERE id = ?");
                $updateVerification->execute([$newId, $userId]);
                $stmt = $pdo->prepare($insertQuery);
                $result = $stmt->execute([
                    $newId,
                    $temp_user['username'],
                    $temp_user['password'],
                    $temp_user['first_name'],
                    $temp_user['last_name'],
                    $verification['telegram_username'],
                    $verification['telegram_chat_id']
                ]);
                if (!$result) {
                    log_message("Повторная попытка не удалась: " . json_encode($stmt->errorInfo()));
                    echo json_encode(['verified' => false, 'message' => 'Не удалось создать пользователя после повторной попытки']);
                    exit;
                }
                log_message("Успешно создан пользователь с ID: " . $newId);
                $_SESSION['user_id'] = $newId;
                $_SESSION['username'] = $temp_user['username'];
                $_SESSION['first_name'] = $temp_user['first_name'];
                $_SESSION['last_name'] = $temp_user['last_name'];
                $_SESSION['telegram'] = $verification['telegram_username'];
                unset($_SESSION['temp_user']);
                echo json_encode(['verified' => true, 'message' => 'Верификация успешна после исправления ошибки дублирования ID']);
            } else {
                log_message("Ошибка PDO: " . $e->getMessage() . " (код: " . $e->getCode() . ")");
                echo json_encode(['verified' => false, 'message' => 'Ошибка верификации: ' . $e->getMessage()]);
            }
        }
    } else {
        log_message("Верификация не найдена для пользователя " . $username);
        echo json_encode(['verified' => false, 'message' => 'Ожидается верификация через Telegram']);
    }
} catch (PDOException $e) {
    log_message("Ошибка PDO: " . $e->getMessage());
    echo json_encode(['verified' => false, 'message' => 'Ошибка верификации: ' . $e->getMessage()]);
} 