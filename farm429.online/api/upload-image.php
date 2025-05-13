<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');
require_once('../config/database.php');
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}
try {
    $user_id = null;
    $headers = getallheaders();
    $api_token = isset($headers['X-Auth-Token']) ? $headers['X-Auth-Token'] : null;
    if ($api_token) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
        $stmt->execute([$api_token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user_id = $user['id'];
        } else {
            throw new Exception('Неверный API токен');
        }
    }
    else if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    else {
        throw new Exception('Пользователь не авторизован. Необходимо указать действительный API токен или войти в систему.');
    }
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла: ' . 
            (isset($_FILES['image']) ? $_FILES['image']['error'] : 'Файл не загружен'));
    }
    $text = isset($_POST['text']) ? $_POST['text'] : 'Фото с камеры';
    $has_analysis = isset($_POST['has_analysis']) && $_POST['has_analysis'] === 'true';
    $analysis_image = null;
    if ($has_analysis && isset($_FILES['analysis_image']) && $_FILES['analysis_image']['error'] === UPLOAD_ERR_OK) {
        $analysis_image = $_FILES['analysis_image'];
    }
    $file = $_FILES['image'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file_tmp);
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Недопустимый тип файла. Разрешены только JPEG, PNG и GIF.');
    }
    $upload_dir = '../uploads/farm_photos/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Не удалось создать директорию для загрузки');
        }
    }
    $timestamp = time();
    $new_file_name = "farm_{$user_id}_{$timestamp}.jpg";
    $file_path = $upload_dir . $new_file_name;
    if (!move_uploaded_file($file_tmp, $file_path)) {
        throw new Exception('Ошибка при сохранении файла');
    }
    $analysis_file_name = null;
    if ($analysis_image) {
        $analysis_file_name = "analysis_{$user_id}_{$timestamp}.jpg";
        $analysis_file_path = $upload_dir . $analysis_file_name;
        if (!move_uploaded_file($analysis_image['tmp_name'], $analysis_file_path)) {
            throw new Exception('Ошибка при сохранении файла анализа');
        }
    }
    $stmt = $pdo->prepare("SELECT temperature, humidity, light_level, co2_level, comment
        FROM farm_status 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1");
    $stmt->execute([$user_id]);
    $lastStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    $temperature = $lastStatus ? $lastStatus['temperature'] : null;
    $humidity = $lastStatus ? $lastStatus['humidity'] : null;
    $light_level = $lastStatus ? $lastStatus['light_level'] : null;
    $co2_level = $lastStatus ? $lastStatus['co2_level'] : null;
    $stmt = $pdo->prepare("INSERT INTO farm_status (
            user_id, 
            temperature,
            humidity,
            light_level,
            co2_level,
            photo, 
            photo_analysis, 
            comment, 
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )
        ON DUPLICATE KEY UPDATE
            temperature = VALUES(temperature),
            humidity = VALUES(humidity),
            light_level = VALUES(light_level),
            co2_level = VALUES(co2_level),
            photo = VALUES(photo),
            photo_analysis = VALUES(photo_analysis),
            comment = VALUES(comment),
            created_at = NOW()");
    $result = $stmt->execute([
        $user_id,
        $temperature,
        $humidity,
        $light_level,
        $co2_level,
        $new_file_name,
        $analysis_file_name,
        $text
    ]);
    if (!$result) {
        throw new Exception('Ошибка при сохранении данных в базу данных');
    }
    echo json_encode([
        'success' => true,
        'message' => 'Фото успешно загружено',
        'user_id' => $user_id,
        'file_path' => '/uploads/farm_photos/' . $new_file_name,
        'analysis_path' => $analysis_file_name ? '/uploads/farm_photos/' . $analysis_file_name : null
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 