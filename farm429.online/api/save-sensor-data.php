<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
require_once('../config/database.php');
require_once 'check-thresholds.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}
try {
    try {
        $pdo->exec("
            ALTER TABLE sensor_data 
            MODIFY temperature DECIMAL(5,2),
            MODIFY humidity DECIMAL(5,2),
            MODIFY soil_moisture DECIMAL(5,2),
            MODIFY light_level DECIMAL(8,2),
            MODIFY pressure DECIMAL(7,2)
        ");
    } catch (PDOException $e) {
        error_log("Ошибка изменения структуры таблицы: " . $e->getMessage());
    }
    $data = $_POST;
    $headers = getallheaders();
    $auth_token = isset($headers['X-Auth-Token']) ? $headers['X-Auth-Token'] : '';
    if (empty($auth_token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Отсутствует токен авторизации']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
    $stmt->execute([$auth_token]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Неверный токен авторизации']);
        exit;
    }
    if (!isset($data['user_id']) || empty($data['user_id'])) {
        $data['user_id'] = $user['id'];
    }
    $required_fields = ['user_id', 'temperature', 'humidity'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Отсутствует обязательное поле: {$field}");
        }
    }
    $user_id = intval($data['user_id']);
    $temperature = number_format(floatval($data['temperature']), 2, '.', '');
    $humidity = number_format(floatval($data['humidity']), 2, '.', '');
    $soil_moisture = number_format(isset($data['soil_moisture']) ? floatval($data['soil_moisture']) : 0, 2, '.', '');
    $light_level = number_format(isset($data['light_level']) ? floatval($data['light_level']) : 0, 2, '.', '');
    $co2 = intval(isset($data['co2']) ? $data['co2'] : 400);
    $pressure = number_format(isset($data['pressure']) ? floatval($data['pressure']) : 1013.25, 2, '.', '');
    $lamp_state = isset($data['lamp_state']) ? (intval($data['lamp_state']) ? 1 : 0) : 0;
    $curtains_state = isset($data['curtains_state']) ? (intval($data['curtains_state']) ? 1 : 0) : 0;
    if ($temperature < -50 || $temperature > 100) {
        throw new Exception("Недопустимое значение температуры");
    }
    if ($humidity < 0 || $humidity > 100) {
        throw new Exception("Недопустимое значение влажности");
    }
    if ($soil_moisture < 0 || $soil_moisture > 100) {
        throw new Exception("Недопустимое значение влажности почвы");
    }
    if ($light_level < 0) {
        throw new Exception("Недопустимое значение освещенности");
    }
    if ($co2 < 0 || $co2 > 5000) {
        throw new Exception("Недопустимое значение CO2");
    }
    if ($pressure < 900 || $pressure > 1100) {
        throw new Exception("Недопустимое значение давления");
    }
    $explicit_id = isset($data['id']) && !empty($data['id']) ? intval($data['id']) : null;
    if ($explicit_id) {
        $check_id = $pdo->prepare("SELECT id FROM sensor_data WHERE id = ?");
        $check_id->execute([$explicit_id]);
        if ($check_id->fetch()) {
            throw new Exception("Запись с ID {$explicit_id} уже существует");
        }
        $sql = "INSERT INTO sensor_data (
            id,
            user_id, 
            temperature, 
            humidity, 
            soil_moisture, 
            light_level, 
            co2, 
            pressure, 
            lamp_state,
            curtains_state,
            created_at
        ) VALUES (
            ?,
            CAST(? AS UNSIGNED), 
            CAST(? AS DECIMAL(5,2)), 
            CAST(? AS DECIMAL(5,2)), 
            CAST(? AS DECIMAL(5,2)), 
            CAST(? AS DECIMAL(8,2)), 
            CAST(? AS UNSIGNED), 
            CAST(? AS DECIMAL(7,2)), 
            ?,
            ?,
            NOW()
        )";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $explicit_id,
            $user_id,
            $temperature,
            $humidity,
            $soil_moisture,
            $light_level,
            $co2,
            $pressure,
            $lamp_state,
            $curtains_state
        ]);
        if (!$result) {
            throw new Exception("Ошибка при сохранении данных с ID {$explicit_id}");
        }
        $saved_id = $explicit_id;
    } else {
        $sql = "INSERT INTO sensor_data (
            user_id, 
            temperature, 
            humidity, 
            soil_moisture, 
            light_level, 
            co2, 
            pressure, 
            lamp_state,
            curtains_state,
            created_at
        ) VALUES (
            CAST(? AS UNSIGNED), 
            CAST(? AS DECIMAL(5,2)), 
            CAST(? AS DECIMAL(5,2)), 
            CAST(? AS DECIMAL(5,2)), 
            CAST(? AS DECIMAL(8,2)), 
            CAST(? AS UNSIGNED), 
            CAST(? AS DECIMAL(7,2)), 
            ?,
            ?,
            NOW()
        )";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $user_id,
            $temperature,
            $humidity,
            $soil_moisture,
            $light_level,
            $co2,
            $pressure,
            $lamp_state,
            $curtains_state
        ]);
        if (!$result) {
            throw new Exception("Ошибка при сохранении данных в базу");
        }
        $saved_id = $pdo->lastInsertId();
    }
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            CAST(temperature AS DECIMAL(5,2)) as temperature,
            CAST(humidity AS DECIMAL(5,2)) as humidity,
            CAST(soil_moisture AS DECIMAL(5,2)) as soil_moisture,
            CAST(light_level AS DECIMAL(8,2)) as light_level,
            co2,
            CAST(pressure AS DECIMAL(7,2)) as pressure,
            lamp_state,
            curtains_state,
            created_at
        FROM sensor_data 
        WHERE id = ?
    ");
    $stmt->execute([$saved_id]);
    $saved_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $sensor_data = [
        'temperature' => floatval($saved_data['temperature']),
        'humidity' => floatval($saved_data['humidity']),
        'soil_moisture' => floatval($saved_data['soil_moisture']),
        'co2' => intval($saved_data['co2'])
    ];
    checkThresholds($user_id, $sensor_data);
    echo json_encode([
        'success' => true,
        'message' => 'Данные успешно сохранены',
        'data' => [
            'id' => $saved_data['id'],
            'user_id' => $user_id,
            'temperature' => floatval($saved_data['temperature']),
            'humidity' => floatval($saved_data['humidity']),
            'soil_moisture' => floatval($saved_data['soil_moisture']),
            'light_level' => floatval($saved_data['light_level']),
            'co2' => intval($saved_data['co2']),
            'pressure' => floatval($saved_data['pressure']),
            'lamp_state' => intval($saved_data['lamp_state']),
            'curtains_state' => intval($saved_data['curtains_state']),
            'created_at' => $saved_data['created_at']
        ]
    ]);
} catch (Exception $e) {
    error_log("Ошибка сохранения данных: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}