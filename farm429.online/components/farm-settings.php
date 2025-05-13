<?php
require_once 'config/database.php';
$isGuest = !isset($_SESSION['user_id']);
$user_id = $isGuest ? 1 : $_SESSION['user_id']; 
function calculateActualHours($startTime, $endTime, $exceptions) {
    $start = timeToMinutes($startTime);
    $end = timeToMinutes($endTime);
    $totalMinutes = $end - $start;
    if ($totalMinutes < 0) {
        $totalMinutes += 24 * 60; 
    }
    if (!empty($exceptions)) {
        foreach ($exceptions as $exception) {
            $exStart = timeToMinutes($exception['start_time']);
            $exEnd = timeToMinutes($exception['end_time']);
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
$stmt = $pdo->prepare("SELECT * FROM lighting_schedule WHERE user_id = ? ORDER BY start_time");
$stmt->execute([$user_id]);
$lighting_schedule = $stmt->fetchAll();
$stmt = $pdo->prepare("
    SELECT required_hours, start_time, end_time 
    FROM lighting_schedule 
    WHERE user_id = :user_id AND is_exception = 0 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([':user_id' => $user_id]);
$lighting = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("
    SELECT start_time, end_time 
    FROM lighting_schedule 
    WHERE user_id = :user_id AND is_exception = 1 
    ORDER BY created_at DESC
");
$stmt->execute([':user_id' => $user_id]);
$exceptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$actual_hours = null;
if ($lighting && !empty($exceptions)) {
    $actual_hours = calculateActualHours($lighting['start_time'], $lighting['end_time'], $exceptions);
}
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'alarm_thresholds'
");
$stmt->execute();
$thresholdsTableExists = (bool) $stmt->fetchColumn();
if (!$thresholdsTableExists) {
    $pdo->exec("
        CREATE TABLE `alarm_thresholds` (
          `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT NOT NULL,
          `parameter_type` ENUM('temperature', 'humidity_air', 'humidity_soil', 'co2') NOT NULL,
          `min_limit` DECIMAL(8,2) NOT NULL,
          `max_limit` DECIMAL(8,2) NOT NULL,
          `target_value` DECIMAL(8,2) DEFAULT NULL COMMENT 'Целевое значение (если необходимо)',
          `tolerance` DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Допустимое отклонение',
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY `user_parameter_unique` (`user_id`, `parameter_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}
$stmt = $pdo->prepare("
    SELECT parameter_type, min_limit, max_limit, target_value, tolerance 
    FROM alarm_thresholds 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$thresholds = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $thresholds[$row['parameter_type']] = $row;
}
$temp_settings = isset($thresholds['temperature']) ? 
    ['temperature' => round($thresholds['temperature']['target_value'], 1), 'tolerance' => round($thresholds['temperature']['tolerance'], 1)] : 
    ['temperature' => 25.0, 'tolerance' => 1.0];
$humidity_settings = isset($thresholds['humidity_air']) ? 
    ['humidity' => round($thresholds['humidity_air']['target_value']), 'tolerance' => round($thresholds['humidity_air']['tolerance'], 1)] : 
    ['humidity' => 60, 'tolerance' => 1.0];
$stmt = $pdo->prepare("SELECT lamp_state, curtains_state FROM sensor_data WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$device_states = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$device_states) {
    $device_states = ['lamp_state' => 0, 'curtains_state' => 0];
}
$temp_limits = isset($thresholds['temperature']) ? 
    ['min_limit' => round($thresholds['temperature']['min_limit'], 1), 'max_limit' => round($thresholds['temperature']['max_limit'], 1)] : 
    ['min_limit' => 15.0, 'max_limit' => 30.0];
$humidity_limits = isset($thresholds['humidity_air']) ? 
    ['min_limit' => round($thresholds['humidity_air']['min_limit']), 'max_limit' => round($thresholds['humidity_air']['max_limit'])] : 
    ['min_limit' => 40, 'max_limit' => 60];
$co2_limits = isset($thresholds['co2']) ? 
    ['min_limit' => round($thresholds['co2']['min_limit']), 'max_limit' => round($thresholds['co2']['max_limit'])] : 
    ['min_limit' => 600, 'max_limit' => 2000];
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'curtains_schedule'
");
$stmt->execute();
$curtainsTableExists = (bool) $stmt->fetchColumn();
if (!$curtainsTableExists) {
    $pdo->exec("
        CREATE TABLE `curtains_schedule` (
          `id` int NOT NULL AUTO_INCREMENT,
          `user_id` int NOT NULL,
          `required_hours` decimal(4,2) DEFAULT NULL,
          `start_time` time NOT NULL,
          `end_time` time NOT NULL,
          `is_exception` tinyint(1) DEFAULT '0',
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ");
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
}
$stmt = $pdo->prepare("SELECT * FROM curtains_schedule WHERE user_id = ? ORDER BY start_time");
$stmt->execute([$user_id]);
$curtains_schedule = $stmt->fetchAll();
$stmt = $pdo->prepare("
    SELECT required_hours, start_time, end_time 
    FROM curtains_schedule 
    WHERE user_id = :user_id AND is_exception = 0 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([':user_id' => $user_id]);
$curtains = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("
    SELECT start_time, end_time 
    FROM curtains_schedule 
    WHERE user_id = :user_id AND is_exception = 1 
    ORDER BY created_at DESC
");
$stmt->execute([':user_id' => $user_id]);
$curtains_exceptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$curtains_actual_hours = null;
if ($curtains && !empty($curtains_exceptions)) {
    $curtains_actual_hours = calculateActualHours($curtains['start_time'], $curtains['end_time'], $curtains_exceptions);
}
?>
<div class="farm-settings-container">
    <div class="farm-settings-header" onclick="toggleFarmSettings()">
        <h2>Настройка фермы</h2>
        <div class="header-right-content">
            <span class="accordion-icon">▼</span>
        </div>
    </div>
    <div class="farm-settings-content" id="farmSettingsContent">
        <?php if ($isGuest): ?>
        <div class="guest-notice">
            <p>Вы просматриваете данные в режиме гостя. Для сохранения изменений необходимо <a href="authentication/login.php">авторизоваться</a>.</p>
        </div>
        <?php endif; ?>
        <div class="settings-grid">
            <!-- Блок температуры -->
            <div class="settings-block temperature-block">
                <h3>Температура</h3>
                <div class="settings-row">
                    <div class="value-input">
                        <label for="temperature">Значение °C</label>
                        <input type="number" id="temperature" min="20" max="50" step="0.1" 
                               value="<?php echo htmlspecialchars($temp_settings['temperature']); ?>" required
                               <?php echo $isGuest ? 'disabled' : ''; ?>
                               aria-label="Установка температуры" 
                               title="Установите целевое значение температуры в градусах Цельсия"
                               placeholder="Целевая температура">
                    </div>
                    <div class="tolerance-input">
                        <label for="temperatureTolerance">Погрешность °C</label>
                        <input type="number" id="temperatureTolerance" min="1" max="5" step="0.1" 
                               value="<?php echo htmlspecialchars($temp_settings['tolerance']); ?>" required
                               <?php echo $isGuest ? 'disabled' : ''; ?>
                               aria-label="Допустимое отклонение температуры" 
                               title="Установите допустимое отклонение от целевой температуры"
                               placeholder="Погрешность">
                    </div>
                </div>
                <?php if (!$isGuest): ?>
                <div class="button-center">
                    <button type="button" class="save-settings" onclick="saveTemperature()" aria-label="Сохранить настройки температуры" title="Сохранить настройки температуры">Сохранить</button>
                </div>
                <?php endif; ?>
            </div>
            <!-- Блок влажности -->
            <div class="settings-block humidity-block">
                <h3>Влажность</h3>
                <div class="settings-row">
                    <div class="value-input">
                        <label for="humidity">Значение %</label>
                        <input type="number" id="humidity" min="30" max="99" 
                               value="<?php echo htmlspecialchars($humidity_settings['humidity']); ?>" required
                               <?php echo $isGuest ? 'disabled' : ''; ?>
                               aria-label="Установка влажности" 
                               title="Установите целевое значение влажности в процентах"
                               placeholder="Целевая влажность">
                    </div>
                    <div class="tolerance-input">
                        <label for="humidityTolerance">Погрешность %</label>
                        <input type="number" id="humidityTolerance" min="1" max="5" step="0.1" 
                               value="<?php echo htmlspecialchars($humidity_settings['tolerance']); ?>" required
                               <?php echo $isGuest ? 'disabled' : ''; ?>
                               aria-label="Допустимое отклонение влажности" 
                               title="Установите допустимое отклонение от целевой влажности"
                               placeholder="Погрешность">
                    </div>
                </div>
                <?php if (!$isGuest): ?>
                <div class="button-center">
                    <button type="button" class="save-settings" onclick="saveHumidity()" aria-label="Сохранить настройки влажности" title="Сохранить настройки влажности">Сохранить</button>
                </div>
                <?php endif; ?>
            </div>
            <!-- Блок управления -->
            <div class="settings-block control-block">
                <h3>Управление</h3>
                <div class="settings-row">
                    <div class="control-buttons">
                        <div class="control-item">
                            <div class="device-status">
                                Лампа <?php echo $device_states['lamp_state'] ? 'включена' : 'выключена'; ?>
                            </div>
                            <?php if (!$isGuest): ?>
                            <button type="button" 
                                    class="control-btn <?php echo $device_states['lamp_state'] ? 'red' : 'green'; ?>"
                                    onclick="toggleLamp()">
                                <?php echo $device_states['lamp_state'] ? '💡 Выключить лампу' : '💡 Включить лампу'; ?>
                            </button>
                            <?php else: ?>
                            <button type="button" class="control-btn disabled" disabled>
                                Требуется авторизация
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="control-item">
                            <div class="device-status">
                                Шторы <?php echo $device_states['curtains_state'] ? 'закрыты' : 'открыты'; ?>
                            </div>
                            <?php if (!$isGuest): ?>
                            <button type="button" 
                                    class="control-btn <?php echo $device_states['curtains_state'] ? 'red' : 'green'; ?>"
                                    onclick="toggleCurtains()">
                                <?php echo $device_states['curtains_state'] ? '🌙 Открыть шторы' : '☀️ Закрыть шторы'; ?>
                            </button>
                            <?php else: ?>
                            <button type="button" class="control-btn disabled" disabled>
                                Требуется авторизация
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Блок расписаний -->
        <div class="schedules-section">
            <div class="schedules-header" onclick="toggleSchedules()">
                <h3>Расписания</h3>
                <div class="header-right-content">
                    <span class="accordion-icon">▼</span>
                </div>
            </div>
            <div class="schedules-content" id="schedulesContent">
                <div class="schedules-grid">
                    <!-- Блок освещения -->
                    <div class="settings-block lighting-block">
                        <h3>Освещение</h3>
                        <div class="lighting-settings">
                            <div class="required-hours">
                                <label for="requiredHours">Время работы (часов)</label>
                                <input type="number" id="requiredHours" min="0" max="24" step="0.5" 
                                       value="<?php echo $actual_hours !== null ? htmlspecialchars($actual_hours) : (isset($lighting['required_hours']) ? htmlspecialchars($lighting['required_hours']) : '15.00'); ?>"
                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                       aria-label="Время работы освещения в часах" 
                                       title="Введите требуемое время работы освещения в часах"
                                       placeholder="Время работы в часах">
                            </div>
                            <div class="time-inputs">
                                <input type="time" id="lightStartTime" 
                                       value="<?php echo isset($lighting['start_time']) ? htmlspecialchars($lighting['start_time']) : '06:00'; ?>"
                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                       aria-label="Время начала освещения" 
                                       title="Время начала периода освещения">
                                <span>ДО</span>
                                <input type="time" id="lightEndTime" 
                                       value="<?php echo isset($lighting['end_time']) ? htmlspecialchars($lighting['end_time']) : '21:00'; ?>"
                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                       aria-label="Время окончания освещения" 
                                       title="Время окончания периода освещения">
                            </div>
                            <h3 class="exceptions-title">Исключения</h3>
                            <div id="exceptions-list">
                                <?php if (!empty($exceptions)): ?>
                                    <?php foreach ($exceptions as $index => $exception): ?>
                                        <div class="exception-item">
                                            <div class="exception-time">
                                                <input type="time" 
                                                       value="<?php echo htmlspecialchars($exception['start_time']); ?>" 
                                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                                       aria-label="Время начала исключения" 
                                                       title="Время начала периода исключения">
                                                <span>до</span>
                                                <input type="time" 
                                                       value="<?php echo htmlspecialchars($exception['end_time']); ?>" 
                                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                                       aria-label="Время окончания исключения" 
                                                       title="Время окончания периода исключения">
                                            </div>
                                            <?php if (!$isGuest): ?>
                                            <button type="button" class="remove-exception" aria-label="Удалить исключение" title="Удалить это исключение">Удалить исключение</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!$isGuest): ?>
                            <div class="button-group">
                                <button type="button" class="add-exception-btn" onclick="addException()" aria-label="Добавить исключение" title="Добавить новое исключение">Добавить исключение</button>
                                <button type="button" class="save-settings" onclick="saveLightingSchedule()" aria-label="Сохранить настройки освещения" title="Сохранить настройки расписания освещения">Сохранить</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Блок штор -->
                    <div class="settings-block curtains-block">
                        <h3>Шторы</h3>
                        <div class="curtains-settings">
                            <div class="required-hours">
                                <label for="curtainsRequiredHours">Время закрытия (часов)</label>
                                <input type="number" id="curtainsRequiredHours" min="0" max="24" step="0.5" 
                                       value="<?php echo $curtains_actual_hours !== null ? htmlspecialchars($curtains_actual_hours) : (isset($curtains['required_hours']) ? htmlspecialchars($curtains['required_hours']) : '12.00'); ?>"
                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                       aria-label="Время закрытия штор в часах" 
                                       title="Введите требуемое время закрытия штор в часах"
                                       placeholder="Время закрытия в часах">
                            </div>
                            <div class="time-inputs">
                                <input type="time" id="curtainsStartTime" 
                                       value="<?php echo isset($curtains['start_time']) ? htmlspecialchars($curtains['start_time']) : '20:00'; ?>"
                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                       aria-label="Время начала закрытия штор" 
                                       title="Время начала периода закрытия штор">
                                <span>ДО</span>
                                <input type="time" id="curtainsEndTime" 
                                       value="<?php echo isset($curtains['end_time']) ? htmlspecialchars($curtains['end_time']) : '08:00'; ?>"
                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                       aria-label="Время окончания закрытия штор" 
                                       title="Время окончания периода закрытия штор">
                            </div>
                            <h3 class="exceptions-title">Исключения</h3>
                            <div id="curtains-exceptions-list">
                                <?php if (!empty($curtains_exceptions)): ?>
                                    <?php foreach ($curtains_exceptions as $index => $exception): ?>
                                        <div class="exception-item">
                                            <div class="exception-time">
                                                <input type="time" 
                                                       value="<?php echo htmlspecialchars($exception['start_time']); ?>" 
                                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                                       aria-label="Время начала исключения" 
                                                       title="Время начала периода исключения">
                                                <span>до</span>
                                                <input type="time" 
                                                       value="<?php echo htmlspecialchars($exception['end_time']); ?>" 
                                                       <?php echo $isGuest ? 'disabled' : ''; ?>
                                                       aria-label="Время окончания исключения" 
                                                       title="Время окончания периода исключения">
                                            </div>
                                            <?php if (!$isGuest): ?>
                                            <button type="button" class="remove-exception" aria-label="Удалить исключение" title="Удалить это исключение">Удалить исключение</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!$isGuest): ?>
                            <div class="button-group">
                                <button type="button" class="add-exception-btn" onclick="addCurtainsException()" aria-label="Добавить исключение" title="Добавить новое исключение">Добавить исключение</button>
                                <button type="button" class="save-settings" onclick="saveCurtainsSchedule()" aria-label="Сохранить настройки штор" title="Сохранить настройки расписания штор">Сохранить</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.limits-section {
    margin-bottom: 20px;
    padding: 15px;
    background: var(--card-bg);
    border-radius: 8px;
}
.limits-section h4 {
    margin: 0 0 10px 0;
    color: var(--text-color);
}
.limit-inputs {
    display: flex;
    gap: 20px;
}
.limit-input {
    display: flex;
    align-items: center;
    gap: 10px;
}
.limit-input label {
    min-width: 80px;
}
.limit-input input {
    width: 100px;
    padding: 5px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background: var(--input-bg);
    color: var(--text-color);
}
.save-limits-btn {
    margin-top: 20px;
    padding: 10px 20px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
}
.save-limits-btn:hover {
    background: var(--primary-hover);
}
[data-theme="dark"] .limits-section {
    background: var(--dark-card-bg, #2a2a2a);
}
[data-theme="dark"] .limit-input input {
    background: var(--dark-input-bg, #333);
    color: var(--dark-text, #fff);
    border-color: var(--dark-border, #444);
}
.guest-notice {
    background-color: rgba(255, 193, 7, 0.2);
    border-left: 4px solid #ffc107;
    padding: 10px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}
.guest-notice p {
    margin: 0;
    color: var(--text-color, #555);
}
.guest-notice a {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}
.guest-notice a:hover {
    text-decoration: underline;
}
button.disabled {
    background-color: #cccccc !important;
    cursor: not-allowed !important;
}
input:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
.save-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #4CAF50;
    color: white;
    padding: 15px 25px;
    border-radius: 5px;
    z-index: 1000;
    animation: fadeInOut 1s ease-in-out;
}
@keyframes fadeInOut {
    0% { opacity: 0; transform: translateY(-20px); }
    20% { opacity: 1; transform: translateY(0); }
    80% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-20px); }
}
.button-center {
    display: flex;
    justify-content: center;
    margin-top: 15px;
}
/* Добавляем стили для блока расписаний */
.schedules-section {
    margin-top: 20px;
    border-radius: 8px;
    overflow: hidden;
    background: var(--card-bg);
}
.schedules-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: var(--primary-color);
    color: white;
    cursor: pointer;
}
.schedules-header h3 {
    margin: 0;
    font-size: 1.3rem;
}
.schedules-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}
.schedules-content.active {
    max-height: 2000px; /* Достаточно большое значение для отображения содержимого */
}
.schedules-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    padding: 20px;
}
/* Добавляем медиа-запрос для больших экранов */
@media (min-width: 911px) {
    .schedules-grid {
        grid-template-columns: 1fr 1fr;
    }
}
/* Центрирование блоков настроек */
.farm-settings-container {
    max-width: 1200px;
    margin: 0 auto;
}
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    justify-content: center;
}
.control-buttons {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}
.control-item {
    width: 100%;
    max-width: 300px;
    text-align: center;
}
.control-btn {
    width: 100%;
}
.device-status {
    margin-bottom: 8px;
    text-align: center;
}
/* Стили для кнопок в блоке расписаний */
.add-exception-btn, .save-settings {
    width: 100%;
    margin-top: 15px;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    color: white;
    transition: background-color 0.3s;
}
.add-exception-btn {
    background-color: #4CAF50;
}
.add-exception-btn:hover {
    background-color: #3e8e41;
}
.save-settings {
    background-color: #4CAF50;
    margin-top: 10px;
}
.save-settings:hover {
    background-color: #3e8e41;
}
.button-group {
    display: flex;
    flex-direction: column;
    margin-top: 15px;
    gap: 10px;
    align-items: center;
}
/* Горизонтальное расположение кнопок на десктопе */
@media (min-width: 768px) {
    .button-group {
        flex-direction: column;
        justify-content: center;
    }
    .button-group button {
        width: 60%;
        margin-top: 10px;
    }
}
/* Улучшаем мобильное отображение */
@media (max-width: 767px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    .time-inputs {
        flex-direction: column;
        align-items: center;
    }
    .time-inputs input[type="time"] {
        margin-bottom: 10px;
        width: 100%;
    }
    .exception-item {
        flex-direction: column;
    }
    .exception-time {
        margin-bottom: 10px;
        width: 100%;
    }
    .remove-exception {
        width: 100%;
    }
    .control-buttons {
        flex-direction: column;
    }
    .control-item {
        margin-bottom: 15px;
        width: 100%;
    }
    .control-btn {
        width: 100%;
    }
    .button-group {
        align-items: center;
    }
    .button-group button {
        max-width: 80%;
    }
}
.time-inputs {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin: 10px 0;
}
.time-inputs input[type="time"] {
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 8px;
    background: var(--input-bg);
    color: var(--text-color);
    font-size: 16px;
    width: 130px;
    text-align: center;
}
.time-inputs span {
    font-weight: bold;
}
/* Стиль для поля "Время закрытия" и приведение к единому виду */
.required-hours {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 15px;
}
.required-hours label {
    margin-bottom: 5px;
}
.required-hours input[type="number"] {
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 8px;
    background: var(--input-bg);
    color: var(--text-color);
    font-size: 16px;
    width: 130px;
    text-align: center;
}
</style>
<script>
function toggleFarmSettings() {
    const content = document.getElementById('farmSettingsContent');
    const icon = document.querySelector('.accordion-icon');
    content.classList.toggle('active');
    icon.classList.toggle('rotate');
}
const isGuest = <?php echo $isGuest ? 'true' : 'false'; ?>;
function calculateTotalHours(startTime, endTime, exceptions) {
    const start = timeToMinutes(startTime);
    const end = timeToMinutes(endTime);
    let totalMinutes = end - start;
    if (totalMinutes < 0) {
        totalMinutes += 24 * 60; 
    }
    exceptions.forEach(exception => {
        const exStart = timeToMinutes(exception.start);
        const exEnd = timeToMinutes(exception.end);
        let exceptionMinutes = exEnd - exStart;
        if (exceptionMinutes < 0) {
            exceptionMinutes += 24 * 60;
        }
        if (isTimeInPeriod(exception.start, startTime, endTime) || 
            isTimeInPeriod(exception.end, startTime, endTime)) {
            totalMinutes -= exceptionMinutes;
        }
    });
    return (totalMinutes / 60).toFixed(2); 
}
function updateDisplayedHours() {
    const startTime = document.getElementById('lightStartTime').value;
    const endTime = document.getElementById('lightEndTime').value;
    const exceptions = [];
    document.querySelectorAll('.exception-item').forEach(item => {
        const inputs = item.querySelectorAll('input[type="time"]');
        exceptions.push({
            start: inputs[0].value,
            end: inputs[1].value
        });
    });
    const calculatedHours = calculateTotalHours(startTime, endTime, exceptions);
    document.getElementById('requiredHours').value = calculatedHours;
}
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('lightStartTime');
    const endTimeInput = document.getElementById('lightEndTime');
    if (startTimeInput && endTimeInput) {
        startTimeInput.addEventListener('change', updateDisplayedHours);
        endTimeInput.addEventListener('change', updateDisplayedHours);
    }
    document.querySelectorAll('.exception-item .remove-exception').forEach(button => {
        button.addEventListener('click', function() {
            if (isGuest) {
                alert('Для удаления исключений необходимо авторизоваться');
                return;
            }
            this.parentElement.remove();
            updateDisplayedHours(); 
        });
    });
    const observer = new MutationObserver(function(mutations) {
        let shouldUpdate = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && 
                (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0)) {
                shouldUpdate = true;
            }
        });
        if (shouldUpdate) {
            updateDisplayedHours();
            document.querySelectorAll('.exception-item input[type="time"]').forEach(input => {
                input.removeEventListener('change', updateDisplayedHours);
                input.addEventListener('change', updateDisplayedHours);
            });
        }
    });
    const exceptionsList = document.getElementById('exceptions-list');
    if (exceptionsList) {
        observer.observe(exceptionsList, { childList: true, subtree: true });
        document.querySelectorAll('.exception-item input[type="time"]').forEach(input => {
            input.addEventListener('change', updateDisplayedHours);
        });
    }
});
function timeToMinutes(timeStr) {
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
}
function isTimeInPeriod(time, start, end) {
    const timeMin = timeToMinutes(time);
    const startMin = timeToMinutes(start);
    const endMin = timeToMinutes(end);
    if (startMin <= endMin) {
        return timeMin >= startMin && timeMin <= endMin;
    } else {
        return timeMin >= startMin || timeMin <= endMin;
    }
}
function saveLightingSchedule(suppressNotifications = false) {
    if (isGuest) {
        alert('Для сохранения настроек необходимо авторизоваться');
        return;
    }
    const requiredHoursInput = document.getElementById('requiredHours');
    const startTime = document.getElementById('lightStartTime').value;
    const endTime = document.getElementById('lightEndTime').value;
    const exceptions = [];
    document.querySelectorAll('.exception-item').forEach(item => {
        const inputs = item.querySelectorAll('input[type="time"]');
        exceptions.push({
            start: inputs[0].value,
            end: inputs[1].value
        });
    });
    const requiredHours = requiredHoursInput.value ? parseFloat(requiredHoursInput.value) : null;
    fetch('/api/save-lighting.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            requiredHours: requiredHours,
            startTime,
            endTime,
            exceptions
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.actual_hours) {
                requiredHoursInput.value = parseFloat(data.actual_hours).toFixed(2);
            }
            if (!suppressNotifications) {
                alert('Настройки освещения сохранены. Фактическое время работы: ' + requiredHoursInput.value + ' часов');
            }
        } else {
            alert(data.message || 'Ошибка при сохранении настроек');
        }
    })
    .catch(error => {
        alert('Ошибка при сохранении настроек: ' + error.message);
    });
}
function saveCurtainsSchedule(suppressNotifications = false) {
    if (isGuest) {
        alert('Для сохранения настроек необходимо авторизоваться');
        return;
    }
    const requiredHoursInput = document.getElementById('curtainsRequiredHours');
    const startTime = document.getElementById('curtainsStartTime').value;
    const endTime = document.getElementById('curtainsEndTime').value;
    const exceptions = [];
    document.querySelectorAll('#curtains-exceptions-list .exception-item').forEach(item => {
        const inputs = item.querySelectorAll('input[type="time"]');
        exceptions.push({
            start: inputs[0].value,
            end: inputs[1].value
        });
    });
    const requiredHours = requiredHoursInput.value ? parseFloat(requiredHoursInput.value) : null;
    fetch('/api/save-curtains.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            requiredHours: requiredHours,
            startTime,
            endTime,
            exceptions
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.actual_hours) {
                requiredHoursInput.value = parseFloat(data.actual_hours).toFixed(2);
            }
            if (!suppressNotifications) {
                alert('Настройки штор сохранены. Фактическое время закрытия: ' + requiredHoursInput.value + ' часов');
            }
        } else {
            alert(data.message || 'Ошибка при сохранении настроек');
        }
    })
    .catch(error => {
        alert('Ошибка при сохранении настроек: ' + error.message);
    });
}
function saveTemperature(suppressNotifications = false) {
    if (isGuest) {
        alert('Для сохранения настроек необходимо авторизоваться');
        return;
    }
    const temperature = parseFloat(document.getElementById('temperature').value);
    const tolerance = parseFloat(document.getElementById('temperatureTolerance').value);
    if (temperature < 20 || temperature > 50) {
        alert('Температура должна быть от 20 до 50°C');
        return;
    }
    if (tolerance < 1 || tolerance > 5) {
        alert('Погрешность должна быть от 1 до 5°C');
        return;
    }
    const temperatureVal = parseFloat(temperature.toFixed(1));
    const toleranceVal = parseFloat(tolerance.toFixed(1));
    const minLimit = parseFloat((temperatureVal - toleranceVal).toFixed(1));
    const maxLimit = parseFloat((temperatureVal + toleranceVal).toFixed(1));
    fetch('/api/save-limits.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            temperature: {
                min: minLimit,
                max: maxLimit,
                target: temperatureVal,
                tolerance: toleranceVal
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (!suppressNotifications) {
                const notification = document.createElement('div');
                notification.className = 'save-notification';
                notification.textContent = 'Температура установлена';
                document.body.appendChild(notification);
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            alert('Ошибка при установке температуры: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        alert('Ошибка при установке температуры: ' + error.message);
    });
}
function saveHumidity(suppressNotifications = false) {
    if (isGuest) {
        alert('Для сохранения настроек необходимо авторизоваться');
        return;
    }
    const humidity = parseFloat(document.getElementById('humidity').value);
    const tolerance = parseFloat(document.getElementById('humidityTolerance').value);
    if (humidity < 30 || humidity > 99) {
        alert('Влажность должна быть от 30 до 99%');
        return;
    }
    if (tolerance < 1 || tolerance > 5) {
        alert('Погрешность должна быть от 1 до 5%');
        return;
    }
    const humidityVal = Math.round(humidity);
    const toleranceVal = parseFloat(tolerance.toFixed(1));
    const minLimit = Math.round(humidityVal - toleranceVal);
    const maxLimit = Math.round(humidityVal + toleranceVal);
    fetch('/api/save-limits.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            humidity: {
                min: minLimit,
                max: maxLimit,
                target: humidityVal,
                tolerance: toleranceVal
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (!suppressNotifications) {
                const notification = document.createElement('div');
                notification.className = 'save-notification';
                notification.textContent = 'Влажность установлена';
                document.body.appendChild(notification);
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            alert('Ошибка при установке влажности: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        alert('Ошибка при установке влажности: ' + error.message);
    });
}
function toggleLamp() {
    if (isGuest) {
        alert('Для управления устройствами необходимо авторизоваться');
        return;
    }
    const lampButton = document.querySelector('.control-buttons .control-item:first-child .control-btn');
    const statusElement = lampButton.closest('.control-item').querySelector('.device-status');
    lampButton.disabled = true;
    lampButton.textContent = 'Загрузка...';
    const formData = new FormData();
    formData.append('action', 'toggle_lamp');
    fetch('/api/toggle-lamp.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}, text: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            lampButton.textContent = data.state ? '💡 Выключить лампу' : '💡 Включить лампу';
            lampButton.classList.toggle('red', data.state);
            lampButton.classList.toggle('green', !data.state);
            statusElement.textContent = `Лампа ${data.state ? 'включена' : 'выключена'}`;
            showNotification(data.state ? 'Лампа включена' : 'Лампа выключена');
        } else {
            showNotification(data.error || 'Ошибка при изменении состояния лампы', 'error');
        }
    })
    .catch(error => {
        showNotification('Ошибка при изменении состояния лампы: ' + error.message, 'error');
    })
    .finally(() => {
        lampButton.disabled = false;
    });
}
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
function toggleCurtains() {
    if (isGuest) {
        alert('Для управления устройствами необходимо авторизоваться');
        return;
    }
    const curtainsButton = document.querySelector('.control-buttons .control-item:last-child .control-btn');
    const statusElement = curtainsButton.closest('.control-item').querySelector('.device-status');
    curtainsButton.disabled = true;
    curtainsButton.textContent = 'Загрузка...';
    const formData = new FormData();
    formData.append('action', 'toggle_curtains');
    fetch('/api/toggle-curtains.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}, text: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            curtainsButton.textContent = data.state ? '☀️ Закрыть шторы' : '🌙 Открыть шторы';
            curtainsButton.classList.toggle('red', data.state);
            curtainsButton.classList.toggle('green', !data.state);
            statusElement.textContent = `Шторы ${data.state ? 'открыты' : 'закрыты'}`;
            showNotification(data.state ? 'Шторы открыты' : 'Шторы закрыты');
        } else {
            showNotification(data.error || 'Ошибка при изменении состояния штор', 'error');
        }
    })
    .catch(error => {
        showNotification('Ошибка при изменении состояния штор: ' + error.message, 'error');
    })
    .finally(() => {
        curtainsButton.disabled = false;
    });
}
function addException() {
    if (document.querySelectorAll('.exception-item').length >= 10) {
        alert('Достигнут максимум исключений (10)');
        return;
    }
    const exceptionsList = document.getElementById('exceptions-list');
    const exceptionItem = document.createElement('div');
    exceptionItem.className = 'exception-item';
    exceptionItem.innerHTML = `
        <div class="exception-time">
            <input type="time" value="08:00" aria-label="Время начала исключения" title="Время начала периода исключения">
            <span>до</span>
            <input type="time" value="12:00" aria-label="Время окончания исключения" title="Время окончания периода исключения">
        </div>
        <button type="button" class="remove-exception" aria-label="Удалить исключение" title="Удалить это исключение">Удалить исключение</button>
    `;
    exceptionsList.appendChild(exceptionItem);
    const newInputs = exceptionItem.querySelectorAll('input[type="time"]');
    newInputs.forEach(input => {
        input.addEventListener('change', updateDisplayedHours);
    });
    const removeButton = exceptionItem.querySelector('.remove-exception');
    removeButton.addEventListener('click', function() {
        if (isGuest) {
            alert('Для удаления исключений необходимо авторизоваться');
            return;
        }
        this.parentElement.remove();
        updateDisplayedHours(); 
    });
    updateDisplayedHours();
}
document.addEventListener('DOMContentLoaded', function() {
});
function saveTemperatureLimits(suppressNotifications = false) {
    if (isGuest) {
        alert('Для сохранения настроек необходимо авторизоваться');
        return;
    }
    const minLimit = parseFloat(document.getElementById('tempMinLimit').value);
    const maxLimit = parseFloat(document.getElementById('tempMaxLimit').value);
    if (minLimit >= maxLimit) {
        alert('Минимальный порог должен быть меньше максимального');
        return;
    }
    const minLimitVal = parseFloat(minLimit.toFixed(1));
    const maxLimitVal = parseFloat(maxLimit.toFixed(1));
    fetch('/api/save-limits.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            temperature: {
                min: minLimitVal,
                max: maxLimitVal
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (!suppressNotifications) {
                const notification = document.createElement('div');
                notification.className = 'save-notification';
                notification.textContent = 'Пороги температуры установлены';
                document.body.appendChild(notification);
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            alert('Ошибка при установке порогов температуры: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        alert('Ошибка при установке порогов температуры: ' + error.message);
    });
}
function saveHumidityLimits(suppressNotifications = false) {
    if (isGuest) {
        alert('Для сохранения настроек необходимо авторизоваться');
        return;
    }
    const minLimit = parseFloat(document.getElementById('humidityMinLimit').value);
    const maxLimit = parseFloat(document.getElementById('humidityMaxLimit').value);
    if (minLimit >= maxLimit) {
        alert('Минимальный порог должен быть меньше максимального');
        return;
    }
    const minLimitVal = Math.round(minLimit);
    const maxLimitVal = Math.round(maxLimit);
    fetch('/api/save-limits.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            humidity: {
                min: minLimitVal,
                max: maxLimitVal
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (!suppressNotifications) {
                const notification = document.createElement('div');
                notification.className = 'save-notification';
                notification.textContent = 'Пороги влажности установлены';
                document.body.appendChild(notification);
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            alert('Ошибка при установке порогов влажности: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        alert('Ошибка при установке порогов влажности: ' + error.message);
    });
}
function toggleLamps(state) {
    if (isGuest) {
        alert('Для управления лампами необходимо авторизоваться');
        return;
    }
    const onButton = document.getElementById('lampsOnButton');
    const offButton = document.getElementById('lampsOffButton');
    onButton.disabled = true;
    offButton.disabled = true;
    document.getElementById('lampsStatus').classList.add('loading');
    fetch('/api/toggle-lamps.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            state: state
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('lampsStatus').classList.remove('loading');
        if (data.success) {
            document.getElementById('lampsStatus').innerText = state ? 'Включены' : 'Выключены';
            if (state) {
                onButton.classList.add('active');
                offButton.classList.remove('active');
            } else {
                onButton.classList.remove('active');
                offButton.classList.add('active');
            }
            showNotification(state ? 'Лампы включены' : 'Лампы выключены', 'success');
        } else {
            showNotification(data.message || 'Ошибка при управлении лампами', 'error');
        }
        onButton.disabled = false;
        offButton.disabled = false;
    })
    .catch(error => {
        document.getElementById('lampsStatus').classList.remove('loading');
        showNotification('Ошибка при управлении лампами: ' + error.message, 'error');
        onButton.disabled = false;
        offButton.disabled = false;
    });
}
function toggleCurtains(state) {
    if (isGuest) {
        alert('Для управления шторами необходимо авторизоваться');
        return;
    }
    const openButton = document.getElementById('curtainsOpenButton');
    const closeButton = document.getElementById('curtainsCloseButton');
    openButton.disabled = true;
    closeButton.disabled = true;
    document.getElementById('curtainsStatus').classList.add('loading');
    fetch('/api/toggle-curtains.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            state: state
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('curtainsStatus').classList.remove('loading');
        if (data.success) {
            document.getElementById('curtainsStatus').innerText = state ? 'Открыты' : 'Закрыты';
            if (state) {
                openButton.classList.add('active');
                closeButton.classList.remove('active');
            } else {
                openButton.classList.remove('active');
                closeButton.classList.add('active');
            }
            showNotification(state ? 'Шторы открыты' : 'Шторы закрыты', 'success');
        } else {
            showNotification(data.message || 'Ошибка при управлении шторами', 'error');
        }
        openButton.disabled = false;
        closeButton.disabled = false;
    })
    .catch(error => {
        document.getElementById('curtainsStatus').classList.remove('loading');
        showNotification('Ошибка при управлении шторами: ' + error.message, 'error');
        openButton.disabled = false;
        closeButton.disabled = false;
    });
}
function addCurtainsException() {
    if (document.querySelectorAll('#curtains-exceptions-list .exception-item').length >= 10) {
        alert('Достигнут максимум исключений (10)');
        return;
    }
    const exceptionsList = document.getElementById('curtains-exceptions-list');
    const exceptionItem = document.createElement('div');
    exceptionItem.className = 'exception-item curtains-exception-item';
    exceptionItem.innerHTML = `
        <div class="exception-time">
            <input type="time" value="12:00" aria-label="Время начала исключения" title="Время начала периода исключения">
            <span>до</span>
            <input type="time" value="16:00" aria-label="Время окончания исключения" title="Время окончания периода исключения">
        </div>
        <button type="button" class="remove-exception" aria-label="Удалить исключение" title="Удалить это исключение">Удалить исключение</button>
    `;
    exceptionsList.appendChild(exceptionItem);
    const newInputs = exceptionItem.querySelectorAll('input[type="time"]');
    newInputs.forEach(input => {
        input.addEventListener('change', updateDisplayedCurtainsHours);
    });
    const removeButton = exceptionItem.querySelector('.remove-exception');
    removeButton.addEventListener('click', function() {
        if (isGuest) {
            alert('Для удаления исключений необходимо авторизоваться');
            return;
        }
        this.parentElement.remove();
        updateDisplayedCurtainsHours(); 
    });
    updateDisplayedCurtainsHours();
}
function updateDisplayedCurtainsHours() {
    const startTime = document.getElementById('curtainsStartTime').value;
    const endTime = document.getElementById('curtainsEndTime').value;
    const exceptions = [];
    document.querySelectorAll('#curtains-exceptions-list .exception-item').forEach(item => {
        const inputs = item.querySelectorAll('input[type="time"]');
        exceptions.push({
            start: inputs[0].value,
            end: inputs[1].value
        });
    });
    const calculatedHours = calculateTotalHours(startTime, endTime, exceptions);
    document.getElementById('curtainsRequiredHours').value = calculatedHours;
}
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('curtainsStartTime');
    const endTimeInput = document.getElementById('curtainsEndTime');
    if (startTimeInput && endTimeInput) {
        startTimeInput.addEventListener('change', updateDisplayedCurtainsHours);
        endTimeInput.addEventListener('change', updateDisplayedCurtainsHours);
    }
    document.querySelectorAll('#curtains-exceptions-list .exception-item .remove-exception').forEach(button => {
        button.addEventListener('click', function() {
            if (isGuest) {
                alert('Для удаления исключений необходимо авторизоваться');
                return;
            }
            this.parentElement.remove();
            updateDisplayedCurtainsHours(); 
        });
    });
    const observer = new MutationObserver(function(mutations) {
        let shouldUpdate = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && 
                (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0)) {
                shouldUpdate = true;
            }
        });
        if (shouldUpdate) {
            updateDisplayedCurtainsHours();
            document.querySelectorAll('#curtains-exceptions-list .exception-item input[type="time"]').forEach(input => {
                input.removeEventListener('change', updateDisplayedCurtainsHours);
                input.addEventListener('change', updateDisplayedCurtainsHours);
            });
        }
    });
    const curtainsExceptionsList = document.getElementById('curtains-exceptions-list');
    if (curtainsExceptionsList) {
        observer.observe(curtainsExceptionsList, { childList: true, subtree: true });
        document.querySelectorAll('#curtains-exceptions-list .exception-item input[type="time"]').forEach(input => {
            input.addEventListener('change', updateDisplayedCurtainsHours);
        });
    }
});
function toggleSchedules() {
    const content = document.getElementById('schedulesContent');
    const icon = document.querySelector('.schedules-header .accordion-icon');
    content.classList.toggle('active');
    if (icon) {
        icon.classList.toggle('rotate');
    }
}
</script> 