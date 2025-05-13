<?php
require_once '../config/database.php';
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as column_exists 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'event_log' 
        AND COLUMN_NAME = 'event_description'
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['column_exists'] == 0) {
        $pdo->exec("ALTER TABLE event_log ADD COLUMN event_description TEXT NOT NULL");
        echo json_encode(['success' => true, 'message' => 'Колонка event_description успешно добавлена']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Колонка event_description уже существует']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении таблицы: ' . $e->getMessage()]);
} 