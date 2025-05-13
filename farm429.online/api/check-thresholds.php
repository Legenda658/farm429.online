<?php
require_once '../config/database.php';
require_once '../config/telegram.php';
function checkThresholds($user_id, $sensor_data) {
    global $pdo;
    $notifications = [];
    try {
        $stmt = $pdo->prepare("
            SELECT temperature, min_limit, max_limit 
            FROM temperature_settings 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $temp_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("
            SELECT humidity, min_limit, max_limit 
            FROM humidity_settings 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $humidity_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("
            SELECT soil_moisture, min_limit, max_limit 
            FROM soil_moisture_settings 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $soil_moisture_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("
            SELECT min_limit, max_limit 
            FROM co2_settings 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $co2_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($temp_settings && isset($sensor_data['temperature'])) {
            if ($sensor_data['temperature'] < $temp_settings['min_limit']) {
                $message = "⚠️ <b>Тревога:</b> Температура ниже допустимой!\n" .
                          "Текущая: {$sensor_data['temperature']}°C\n" .
                          "Минимальная: {$temp_settings['min_limit']}°C";
                $notifications[] = ['type' => 'temperature', 'message' => $message];
            } elseif ($sensor_data['temperature'] > $temp_settings['max_limit']) {
                $message = "⚠️ <b>Тревога:</b> Температура выше допустимой!\n" .
                          "Текущая: {$sensor_data['temperature']}°C\n" .
                          "Максимальная: {$temp_settings['max_limit']}°C";
                $notifications[] = ['type' => 'temperature', 'message' => $message];
            }
        }
        if ($humidity_settings && isset($sensor_data['humidity'])) {
            if ($sensor_data['humidity'] < $humidity_settings['min_limit']) {
                $message = "⚠️ <b>Тревога:</b> Влажность воздуха ниже допустимой!\n" .
                          "Текущая: {$sensor_data['humidity']}%\n" .
                          "Минимальная: {$humidity_settings['min_limit']}%";
                $notifications[] = ['type' => 'humidity', 'message' => $message];
            } elseif ($sensor_data['humidity'] > $humidity_settings['max_limit']) {
                $message = "⚠️ <b>Тревога:</b> Влажность воздуха выше допустимой!\n" .
                          "Текущая: {$sensor_data['humidity']}%\n" .
                          "Максимальная: {$humidity_settings['max_limit']}%";
                $notifications[] = ['type' => 'humidity', 'message' => $message];
            }
        }
        if ($soil_moisture_settings && isset($sensor_data['soil_moisture'])) {
            if ($sensor_data['soil_moisture'] < $soil_moisture_settings['min_limit']) {
                $message = "⚠️ <b>Тревога:</b> Влажность почвы ниже допустимой!\n" .
                          "Текущая: {$sensor_data['soil_moisture']}%\n" .
                          "Минимальная: {$soil_moisture_settings['min_limit']}%";
                $notifications[] = ['type' => 'soil_moisture', 'message' => $message];
            } elseif ($sensor_data['soil_moisture'] > $soil_moisture_settings['max_limit']) {
                $message = "⚠️ <b>Тревога:</b> Влажность почвы выше допустимой!\n" .
                          "Текущая: {$sensor_data['soil_moisture']}%\n" .
                          "Максимальная: {$soil_moisture_settings['max_limit']}%";
                $notifications[] = ['type' => 'soil_moisture', 'message' => $message];
            }
        }
        if ($co2_settings && isset($sensor_data['co2'])) {
            if ($sensor_data['co2'] < $co2_settings['min_limit']) {
                $message = "⚠️ <b>Тревога:</b> Уровень CO2 ниже допустимого!\n" .
                          "Текущий: {$sensor_data['co2']} ppm\n" .
                          "Минимальный: {$co2_settings['min_limit']} ppm";
                $notifications[] = ['type' => 'co2', 'message' => $message];
            } elseif ($sensor_data['co2'] > $co2_settings['max_limit']) {
                $message = "⚠️ <b>Тревога:</b> Уровень CO2 выше допустимого!\n" .
                          "Текущий: {$sensor_data['co2']} ppm\n" .
                          "Максимальный: {$co2_settings['max_limit']} ppm";
                $notifications[] = ['type' => 'co2', 'message' => $message];
            }
        }
        foreach ($notifications as $notification) {
            sendAlarmNotification($user_id, $notification['message']);
            $stmt = $pdo->prepare("
                INSERT INTO event_log (user_id, event_type, event_description) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $notification['type'], strip_tags($notification['message'])]);
        }
        return !empty($notifications);
    } catch (Exception $e) {
        error_log("Error checking thresholds: " . $e->getMessage());
        return false;
    }
} 