<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/headers.php'; 
header('Content-Type: application/json');
set_ajax_cache_headers(false, 0);
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Пользователь не авторизован'
    ]);
    exit;
}
try {
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT lamp_state FROM sensor_data WHERE user_id = ?");
    $stmt->execute([$userId]);
    $sensorData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sensorData) {
        $newLampState = $sensorData['lamp_state'] == 1 ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE sensor_data SET lamp_state = ? WHERE user_id = ?");
        $result = $stmt->execute([$newLampState, $userId]);
        if ($result) {
            echo json_encode([
                'success' => true,
                'lamp_state' => $newLampState
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Ошибка при изменении состояния лампы'
            ]);
        }
    } else {
        try {
            $stmt = $pdo->query("SELECT MAX(id) as max_id FROM sensor_data");
            $maxId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
            $newId = $maxId ? $maxId + 1 : 1;
            $stmt = $pdo->prepare("INSERT INTO sensor_data (id, user_id, temperature, humidity, co2, soil_moisture, light_level, pressure, curtains_state, lamp_state) 
                                  VALUES (?, ?, 25.0, 60.0, 800, 50.0, 1000.0, 760.0, 0, 1)");
            $result = $stmt->execute([$newId, $userId]);
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'lamp_state' => 1
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Ошибка при создании записи с состоянием лампы'
                ]);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { 
                try {
                    $newId = $maxId + 10; 
                    $stmt = $pdo->prepare("INSERT INTO sensor_data (id, user_id, temperature, humidity, co2, soil_moisture, light_level, pressure, curtains_state, lamp_state) 
                                         VALUES (?, ?, 25.0, 60.0, 800, 50.0, 1000.0, 760.0, 0, 1)");
                    $result = $stmt->execute([$newId, $userId]);
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'lamp_state' => 1
                        ]);
                        exit;
                    }
                } catch (PDOException $e2) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Ошибка при создании записи с альтернативным ID'
                    ]);
                    exit;
                }
            }
            echo json_encode([
                'success' => false,
                'error' => 'Ошибка базы данных при обработке запроса'
            ]);
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Произошла ошибка при обработке запроса'
    ]);
}
?> 
 