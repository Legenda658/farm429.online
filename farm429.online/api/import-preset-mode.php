<?php
require_once "../config/database.php";
require_once '../config/headers.php'; 
session_start();
header("Content-Type: application/json");
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';
if (empty($code) || strlen($code) !== 8) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Недействительный код импорта. Код должен содержать 8 символов.']);
    exit;
}
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM preset_modes WHERE share_code = ?");
    $stmt->execute([$code]);
    $mode = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mode) {
        echo json_encode(['success' => false, 'message' => 'Режим с указанным кодом не найден']);
        exit;
    }
    if ($mode['user_id'] == $user_id) {
        echo json_encode(['success' => false, 'message' => 'Вы не можете импортировать свой собственный режим']);
        exit;
    }
    $insertStmt = $pdo->prepare("INSERT INTO preset_modes (
            user_id, name, temperature, tolerance, 
            humidity, humidity_tolerance, light_hours, 
            light_start, light_end, created_at
        ) VALUES (
            :user_id, :name, :temperature, :tolerance, 
            :humidity, :humidity_tolerance, :light_hours, 
            :light_start, :light_end, NOW()
        )");
    $newName = $mode['name'] . ' (импорт)';
    $insertStmt->execute([
        ':user_id' => $user_id,
        ':name' => $newName,
        ':temperature' => $mode['temperature'],
        ':tolerance' => $mode['tolerance'],
        ':humidity' => $mode['humidity'],
        ':humidity_tolerance' => $mode['humidity_tolerance'],
        ':light_hours' => $mode['light_hours'],
        ':light_start' => $mode['light_start'],
        ':light_end' => $mode['light_end']
    ]);
    $newPresetId = $pdo->lastInsertId();
    echo json_encode([
        'success' => true, 
        'message' => 'Режим успешно импортирован',
        'presetId' => $newPresetId
    ]);
} catch (PDOException $e) {
    error_log('Ошибка при импорте режима: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных при импорте режима']);
} catch (Exception $e) {
    error_log('Общая ошибка: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка при импорте режима']);
}
?> 