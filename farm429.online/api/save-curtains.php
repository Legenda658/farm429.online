<?php
require_once '../config/database.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
if (!isset($data['startTime']) || !isset($data['endTime'])) {
    echo json_encode(['success' => false, 'message' => 'Не все данные предоставлены']);
    exit;
}
function calculateTotalHours($startTime, $endTime, $exceptions) {
    $start = timeToMinutes($startTime);
    $end = timeToMinutes($endTime);
    $totalMinutes = $end - $start;
    if ($totalMinutes < 0) {
        $totalMinutes += 24 * 60; 
    }
    if (!empty($exceptions)) {
        foreach ($exceptions as $exception) {
            $exStart = timeToMinutes($exception['start']);
            $exEnd = timeToMinutes($exception['end']);
            $exceptionMinutes = $exEnd - $exStart;
            if ($exceptionMinutes < 0) {
                $exceptionMinutes += 24 * 60; 
            }
            if (isTimeInPeriod($exStart, $exEnd, $start, $end)) {
                $overlapStart = max($start, $exStart);
                $overlapEnd = min($end, $exEnd);
                if ($end < $start) { 
                    if ($exEnd <= $start || $exStart >= $end) {
                        continue; 
                    }
                    if ($exStart < $start && $exEnd > $start) {
                        $overlapMinutes = $exEnd - $start;
                        if ($overlapMinutes < 0) $overlapMinutes += 24 * 60;
                        $totalMinutes -= $overlapMinutes;
                    } else if ($exStart < $end && $exEnd > $end) {
                        $overlapMinutes = $end - $exStart;
                        if ($overlapMinutes < 0) $overlapMinutes += 24 * 60;
                        $totalMinutes -= $overlapMinutes;
                    } else {
                        $totalMinutes -= $exceptionMinutes;
                    }
                } else {
                    $overlapMinutes = $overlapEnd - $overlapStart;
                    if ($overlapMinutes > 0) {
                        $totalMinutes -= $overlapMinutes;
                    }
                }
            }
        }
    }
    return round($totalMinutes / 60, 2); 
}
function timeToMinutes($timeStr) {
    list($hours, $minutes) = explode(':', $timeStr);
    return (int)$hours * 60 + (int)$minutes;
}
function isTimeInPeriod($exStart, $exEnd, $start, $end) {
    if ($start <= $end) { 
        if ($exStart <= $exEnd) { 
            return max($start, $exStart) < min($end, $exEnd);
        } else { 
            return ($exStart < $end) || ($exEnd > $start);
        }
    } else { 
        if ($exStart <= $exEnd) { 
            return ($exStart < $end) || ($exEnd > $start);
        } else { 
            return true; 
        }
    }
}
try {
    $pdo->beginTransaction();
    $exceptions = [];
    if (isset($data['exceptions']) && is_array($data['exceptions'])) {
        foreach ($data['exceptions'] as $exception) {
            $exceptions[] = [
                'start' => $exception['start'],
                'end' => $exception['end']
            ];
        }
    }
    $actualHours = calculateTotalHours($data['startTime'], $data['endTime'], $exceptions);
    if (isset($data['requiredHours'])) {
        $requiredHours = floatval($data['requiredHours']);
        if (abs($requiredHours - $actualHours) > 0.01) {
            $requiredHours = $actualHours;
        }
    } else {
        $requiredHours = $actualHours;
    }
    $checkStmt = $pdo->prepare("SELECT id FROM curtains_schedule WHERE user_id = :user_id AND is_exception = 0 LIMIT 1");
    $checkStmt->execute([':user_id' => $user_id]);
    $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if ($existingRecord) {
        $stmt = $pdo->prepare("UPDATE curtains_schedule SET 
            required_hours = :required_hours,
            start_time = :start_time,
            end_time = :end_time
            WHERE id = :id");
        $stmt->execute([
            ':id' => $existingRecord['id'],
            ':required_hours' => $requiredHours,
            ':start_time' => $data['startTime'],
            ':end_time' => $data['endTime']
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO curtains_schedule (user_id, required_hours, start_time, end_time, is_exception) 
            VALUES (:user_id, :required_hours, :start_time, :end_time, 0)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':required_hours' => $requiredHours,
            ':start_time' => $data['startTime'],
            ':end_time' => $data['endTime']
        ]);
    }
    $stmt = $pdo->prepare("DELETE FROM curtains_schedule WHERE user_id = :user_id AND is_exception = 1");
    $stmt->execute([':user_id' => $user_id]);
    if (!empty($exceptions)) {
        $stmt = $pdo->prepare("INSERT INTO curtains_schedule (user_id, start_time, end_time, is_exception) 
            VALUES (:user_id, :start_time, :end_time, 1)");
        foreach ($exceptions as $exception) {
            $stmt->execute([
                ':user_id' => $user_id,
                ':start_time' => $exception['start'],
                ':end_time' => $exception['end']
            ]);
        }
    }
    $stmt = $pdo->prepare("SELECT temperature, humidity, photo, photo_analysis, comment, co2_level, light_level
        FROM farm_status 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $lastStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    $temperature = $lastStatus ? $lastStatus['temperature'] : null;
    $humidity = $lastStatus ? $lastStatus['humidity'] : null;
    $photo = $lastStatus ? $lastStatus['photo'] : null;
    $photo_analysis = $lastStatus ? $lastStatus['photo_analysis'] : null;
    $comment = $lastStatus ? $lastStatus['comment'] : null;
    $co2_level = $lastStatus ? $lastStatus['co2_level'] : null;
    $light_level = $lastStatus ? $lastStatus['light_level'] : null;
    $checkStatusStmt = $pdo->prepare("SELECT id FROM farm_status WHERE user_id = :user_id LIMIT 1");
    $checkStatusStmt->execute([':user_id' => $user_id]);
    $existingStatus = $checkStatusStmt->fetch(PDO::FETCH_ASSOC);
    $checkColumnStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'farm_status' 
        AND COLUMN_NAME = 'curtains_level'
    ");
    $checkColumnStmt->execute();
    $columnExists = (bool) $checkColumnStmt->fetchColumn();
    if (!$columnExists) {
        $pdo->exec("ALTER TABLE farm_status ADD COLUMN curtains_level DECIMAL(4,2) DEFAULT NULL AFTER light_level");
    }
    if ($existingStatus) {
        $stmt = $pdo->prepare("UPDATE farm_status SET 
            curtains_level = :curtains_level, 
            created_at = NOW() 
            WHERE user_id = :user_id");
        $stmt->execute([
            ':user_id' => $user_id,
            ':curtains_level' => $requiredHours
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO farm_status (user_id, temperature, humidity, light_level, curtains_level, photo, photo_analysis, comment, co2_level, created_at)
            VALUES (:user_id, :temperature, :humidity, :light_level, :curtains_level, :photo, :photo_analysis, :comment, :co2_level, NOW())");
        $stmt->execute([
            ':user_id' => $user_id,
            ':temperature' => $temperature,
            ':humidity' => $humidity,
            ':light_level' => $light_level,
            ':curtains_level' => $requiredHours,
            ':photo' => $photo,
            ':photo_analysis' => $photo_analysis,
            ':comment' => $comment,
            ':co2_level' => $co2_level
        ]);
    }
    $stmtEventLog = $pdo->prepare("INSERT INTO event_log (user_id, event_type, event_description) VALUES (:user_id, 'curtains', :description)");
    $stmtEventLog->execute([
        ':user_id' => $user_id,
        ':description' => 'Установлено новое значение времени закрытия штор: ' . $requiredHours . ' часов'
    ]);
    $pdo->commit();
    echo json_encode(['success' => true, 'actual_hours' => $actualHours]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении: ' . $e->getMessage()]);
} 