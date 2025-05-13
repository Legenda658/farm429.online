<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/headers.php'; 
header('Content-Type: application/json');
require_once '../config/headers.php';
set_ajax_cache_headers(false, 0);
function log_error($message) {
    error_log("[" . date("Y-m-d H:i:s") . "] toggle-curtains.php: " . $message, 3, "../logs/api_errors.log");
}
function log_action($message) {
    error_log("[" . date("Y-m-d H:i:s") . "] toggle-curtains.php: " . $message, 3, "../logs/actions.log");
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Пользователь не авторизован'
    ]);
    exit;
}
try {
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT id, curtains_state FROM sensor_data WHERE user_id = ?");
    $stmt->execute([$userId]);
    $sensorData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sensorData) {
        $newCurtainsState = $sensorData['curtains_state'] ? 0 : 1;
        try {
            $stmt = $pdo->prepare("UPDATE sensor_data SET curtains_state = ? WHERE user_id = ?");
            $result = $stmt->execute([$newCurtainsState, $userId]);
            if ($result) {
                log_action("Пользователь $userId изменил состояние штор на " . ($newCurtainsState ? "открыто" : "закрыто"));
                echo json_encode([
                    'success' => true,
                    'state' => $newCurtainsState,
                    'message' => 'Состояние штор успешно изменено'
                ]);
            } else {
                log_error("Ошибка при обновлении состояния штор для пользователя $userId");
                echo json_encode([
                    'success' => false,
                    'error' => 'Ошибка при обновлении состояния штор'
                ]);
            }
        } catch (PDOException $e) {
            log_error("PDOException при обновлении: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Ошибка базы данных при обновлении состояния'
            ]);
        }
    } else {
        try {
            $stmt = $pdo->query("SELECT MAX(id) as max_id FROM sensor_data");
            $maxId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
            $newId = $maxId ? $maxId + 1 : 1;
            $stmt = $pdo->prepare("INSERT INTO sensor_data (id, user_id, temperature, humidity, co2, soil_moisture, light_level, pressure, curtains_state, lamp_state) 
                                  VALUES (?, ?, 25.0, 60.0, 800, 50.0, 1000.0, 760.0, 1, 0)");
            $result = $stmt->execute([$newId, $userId]);
            if ($result) {
                log_action("Создана новая запись для пользователя $userId со шторами в открытом состоянии");
                echo json_encode([
                    'success' => true,
                    'state' => 1,
                    'message' => 'Шторы успешно открыты'
                ]);
            } else {
                log_error("Ошибка при создании записи со шторами для пользователя $userId");
                echo json_encode([
                    'success' => false,
                    'error' => 'Ошибка при создании записи'
                ]);
            }
        } catch (PDOException $e) {
            log_error("PDOException при вставке: " . $e->getMessage() . ", Код: " . $e->getCode());
            if ($e->getCode() == 23000) { 
                try {
                    $newId = $maxId + 10; 
                    $stmt = $pdo->prepare("INSERT INTO sensor_data (id, user_id, temperature, humidity, co2, soil_moisture, light_level, pressure, curtains_state, lamp_state) 
                                         VALUES (?, ?, 25.0, 60.0, 800, 50.0, 1000.0, 760.0, 1, 0)");
                    $result = $stmt->execute([$newId, $userId]);
                    if ($result) {
                        log_action("Создана новая запись с альтернативным ID для пользователя $userId");
                        echo json_encode([
                            'success' => true,
                            'state' => 1,
                            'message' => 'Шторы успешно открыты (альтернативный ID)'
                        ]);
                    } else {
                        log_error("Ошибка при создании записи с альтернативным ID для пользователя $userId");
                        echo json_encode([
                            'success' => false,
                            'error' => 'Ошибка при создании записи с альтернативным ID'
                        ]);
                    }
                } catch (PDOException $e2) {
                    log_error("Вторая PDOException: " . $e2->getMessage());
                    echo json_encode([
                        'success' => false,
                        'error' => 'Ошибка при создании записи с альтернативным ID'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Ошибка базы данных при обработке запроса'
                ]);
            }
        }
    }
} catch (Exception $e) {
    log_error("Общая ошибка: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Произошла ошибка при обработке запроса'
    ]);
}
?> 
 