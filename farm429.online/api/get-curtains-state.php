<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';
require_once '../config/headers.php'; 
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Auth-Token');
set_ajax_cache_headers(true, 30);
function log_message($message) {
    $log_dir = dirname(__FILE__) . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    error_log("[" . date("Y-m-d H:i:s") . "] get-curtains-state.php: " . $message . "\n", 3, $log_dir . "/api_activity.log");
}
try {
    $headers = getallheaders();
    $token = isset($headers['X-Auth-Token']) ? $headers['X-Auth-Token'] : '';
    log_message("Получен токен: " . $token);
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Отсутствует токен авторизации']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    log_message("Найден пользователь: " . ($user ? "ID: " . $user['id'] : "Пользователь не найден"));
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Неверный токен авторизации']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT curtains_state FROM sensor_data WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user['id']]);
    $sensorData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sensorData) {
        log_message("Получено состояние штор из sensor_data: " . $sensorData['curtains_state']);
        $curtains_state = (int)$sensorData['curtains_state'];
        echo json_encode([
            'success' => true,
            'state' => $curtains_state
        ]);
    } else {
        log_message("Не найдены данные в sensor_data, создаем запись по умолчанию");
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM sensor_data");
        $maxId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
        $newId = $maxId ? $maxId + 1 : 1;
        $stmt = $pdo->prepare("INSERT INTO sensor_data (id, user_id, temperature, humidity, co2, soil_moisture, light_level, pressure, curtains_state, lamp_state) 
                              VALUES (?, ?, 25.0, 60.0, 800, 50.0, 1000.0, 760.0, 1, 0)");
        $result = $stmt->execute([$newId, $user['id']]);
        if ($result) {
            log_message("Создана новая запись с ID: $newId и состоянием штор: 1");
            echo json_encode([
                'success' => true,
                'state' => 1
            ]);
        } else {
            log_message("Ошибка при создании записи в базе данных");
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка при создании записи с состоянием штор'
            ]);
        }
    }
} catch (PDOException $e) {
    log_message("PDO ошибка: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    log_message("Общая ошибка: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сервера: ' . $e->getMessage()
    ]);
} 