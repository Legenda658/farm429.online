<?php
require_once '../config/database.php';
require_once '../config/headers.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
try {
    if (!isset($_GET['user_id'])) {
        throw new Exception('ID пользователя не указан');
    }
    $user_id = intval($_GET['user_id']);
    $stmt = $pdo->prepare("SELECT photo, photo_analysis FROM farm_status WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        throw new Exception('Данные не найдены');
    }
    $response = [
        'success' => true,
        'photo' => $result['photo'] ? '/uploads/farm_photos/' . $result['photo'] : null,
        'photo_analysis' => $result['photo_analysis'] ?: null
    ];
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 