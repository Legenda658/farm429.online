<?php
require_once '../config/database.php';
require_once '../config/headers.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
set_ajax_cache_headers(true, 10);
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}
$user_id = $_SESSION['user_id'];
$period = $_GET['period'] ?? 'day';
$type = $_GET['type'] ?? 'all';
try {
    $interval = $period === 'week' ? '7 days' : '24 hours';
    $sql = "SELECT temperature, humidity, co2, created_at 
            FROM sensor_data 
            WHERE user_id = :user_id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
            ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [
        'labels' => [],
        'datasets' => [
            'temperature' => [],
            'humidity' => [],
            'co2' => []
        ]
    ];
    foreach ($data as $row) {
        $result['labels'][] = date('H:i', strtotime($row['created_at']));
        $result['datasets']['temperature'][] = floatval($row['temperature']);
        $result['datasets']['humidity'][] = intval($row['humidity']);
        $result['datasets']['co2'][] = intval($row['co2']);
    }
    echo json_encode(['success' => true, 'data' => $result]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при получении данных: ' . $e->getMessage()]);
}