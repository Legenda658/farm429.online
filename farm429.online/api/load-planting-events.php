<?php
require_once '../config/database.php';
require_once '../config/headers.php'; 
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
set_ajax_cache_headers(true, 120);
if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($success, $message, $data = []) {
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit;
    }
}
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Необходима авторизация');
}
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$user_id = $_SESSION['user_id'];
try {
    if (!isset($pdo)) {
        throw new Exception("Ошибка подключения к базе данных.");
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($month > 0) {
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
    } else {
        $start_date = sprintf('%04d-01-01', $year);
        $end_date = sprintf('%04d-12-31', $year);
    }
    $sql = "SELECT 
                e.id, e.user_id, e.type, e.plant_name, e.event_date, e.event_time, e.notes, e.created_at, 
                r.id as reminder_id, r.reminder_date, r.reminder_time, r.is_shown as reminder_shown 
            FROM planting_events e
            LEFT JOIN planting_reminders r ON e.id = r.event_id
            WHERE e.user_id = :user_id 
              AND e.event_date BETWEEN :start_date AND :end_date
            ORDER BY e.event_date, e.event_time";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $events = [];
    foreach ($results as $row) {
        $event_id = $row['id'];
        if (!isset($events[$event_id])) {
            $events[$event_id] = [
                'id' => (int)$row['id'],
                'user_id' => (int)$row['user_id'],
                'type' => $row['type'],
                'plant_name' => $row['plant_name'],
                'event_date' => $row['event_date'],
                'event_time' => $row['event_time'],
                'notes' => $row['notes'],
                'created_at' => $row['created_at'],
                'reminder' => null
            ];
        }
        if ($row['reminder_id']) {
            $events[$event_id]['reminder'] = [
                'id' => (int)$row['reminder_id'],
                'date' => $row['reminder_date'],
                'time' => $row['reminder_time'],
                'is_shown' => (bool)$row['reminder_shown']
            ];
        }
    }
    $events = array_values($events);
    sendJsonResponse(true, 'События успешно загружены', ['events' => $events]);
} catch (PDOException $e) {
    error_log('PDO Ошибка при загрузке событий: ' . $e->getMessage());
    sendJsonResponse(false, 'Ошибка базы данных при загрузке событий: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Общая ошибка при загрузке событий: ' . $e->getMessage());
    sendJsonResponse(false, 'Произошла ошибка при загрузке событий: ' . $e->getMessage());
}
?> 