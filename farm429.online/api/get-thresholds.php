<?php
require_once '../config/database.php';
require_once '../config/headers.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Auth-Token');
set_ajax_cache_headers(true, 300);
try {
    $headers = getallheaders();
    $token = isset($headers['X-Auth-Token']) ? $headers['X-Auth-Token'] : '';
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Отсутствует токен авторизации']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Неверный токен авторизации']);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT min_limit, max_limit 
        FROM temperature_settings 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $temp_settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['min_limit' => 20, 'max_limit' => 30];
    $stmt = $pdo->prepare("
        SELECT min_limit, max_limit 
        FROM humidity_settings 
        WHERE user_id = ? AND type = 'air'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $humidity_settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['min_limit' => 40, 'max_limit' => 60];
    $stmt = $pdo->prepare("
        SELECT min_limit, max_limit 
        FROM humidity_settings 
        WHERE user_id = ? AND type = 'soil'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $soil_moisture_settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['min_limit' => 30, 'max_limit' => 70];
    $stmt = $pdo->prepare("
        SELECT min_limit, max_limit 
        FROM co2_settings 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $co2_settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['min_limit' => 400, 'max_limit' => 2000];
    $response = [
        'temperature' => [
            'min' => floatval($temp_settings['min_limit']),
            'max' => floatval($temp_settings['max_limit'])
        ],
        'humidity' => [
            'min' => floatval($humidity_settings['min_limit']),
            'max' => floatval($humidity_settings['max_limit'])
        ],
        'soil_moisture' => [
            'min' => floatval($soil_moisture_settings['min_limit']),
            'max' => floatval($soil_moisture_settings['max_limit'])
        ],
        'co2' => [
            'min' => floatval($co2_settings['min_limit']),
            'max' => floatval($co2_settings['max_limit'])
        ]
    ];
    echo json_encode($response);
} catch (Exception $e) {
    error_log('Error in get-thresholds.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()]);
} 