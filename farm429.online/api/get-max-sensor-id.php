<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';
require_once '../config/headers.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Auth-Token');
set_ajax_cache_headers(false, 0);
function log_message($message) {
    $log_dir = dirname(__FILE__) . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    error_log("[" . date("Y-m-d H:i:s") . "] get-max-sensor-id.php: " . $message . "\n", 3, $log_dir . "/api_activity.log");
}
try {
    $headers = getallheaders();
    $token = isset($headers['X-Auth-Token']) ? $headers['X-Auth-Token'] : '';
    if (empty($token)) {
        log_message("Отсутствует токен авторизации");
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Отсутствует токен авторизации']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) {
        log_message("Неверный токен авторизации: " . $token);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Неверный токен авторизации']);
        exit;
    }
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM sensor_data");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $max_id = ($result && $result['max_id'] !== null) ? (int)$result['max_id'] : 0;
    log_message("Успешный запрос max_id. Пользователь ID: " . $user['id'] . ", Результат: " . $max_id);
    echo json_encode([
        'success' => true,
        'max_id' => $max_id,
        'user_id' => $user['id'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
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