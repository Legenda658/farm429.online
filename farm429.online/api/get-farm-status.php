<?php
require_once '../config/database.php';
require_once '../config/session.php';
header('Content-Type: application/json');
require_once '../config/headers.php';
set_ajax_cache_headers(true, 60);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM farm_status WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$status) {
        echo json_encode([
            'success' => false,
            'message' => 'Данные о статусе фермы не найдены'
        ]);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT temperature, humidity, co2, soil_moisture, light_level, pressure, curtains_state, lamp_state 
        FROM sensor_data 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $sensors = $stmt->fetch(PDO::FETCH_ASSOC);
    $response = [
        'success' => true,
        'id' => $status['id'],
        'user_id' => $user_id,
        'photo' => $status['photo'],
        'photo_analysis' => $status['photo_analysis'],
        'comment' => nl2br(htmlspecialchars($status['comment'])),
        'created_at' => $status['created_at'],
        'sensors' => $sensors ? [
            'temperature' => number_format((float)$sensors['temperature'], 1, '.', ''),
            'humidity' => number_format((float)$sensors['humidity'], 1, '.', ''),
            'co2' => intval($sensors['co2']),
            'soil_moisture' => number_format((float)$sensors['soil_moisture'], 1, '.', ''),
            'light_level' => number_format((float)$sensors['light_level'], 1, '.', ''),
            'pressure' => number_format((float)$sensors['pressure'], 1, '.', ''),
            'curtains_state' => (bool)$sensors['curtains_state'],
            'lamp_state' => (bool)$sensors['lamp_state']
        ] : null
    ];
    echo json_encode($response);
} catch (PDOException $e) {
    error_log("Ошибка при получении данных о ферме: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при получении данных о ферме'
    ]);
} 
 