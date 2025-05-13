<?php
require_once __DIR__ . '/../config/database.php';
$isGuest = !isset($_SESSION['user_id']);
$user_id = $isGuest ? 1 : $_SESSION['user_id']; 
function generateDemoDataForDay($type) {
    $data = [];
    $now = time();
    $startValue = 0;
    $maxChange = 0;
    switch ($type) {
        case 'temperature':
            $startValue = 23.5;
            $maxChange = 2.5;
            break;
        case 'humidity':
            $startValue = 55;
            $maxChange = 10;
            break;
        case 'co2':
            $startValue = 800;
            $maxChange = 200;
            break;
        case 'soil_moisture':
            $startValue = 65;
            $maxChange = 8;
            break;
        case 'light_level':
            $startValue = 5000;
            $maxChange = 2000;
            break;
        case 'pressure':
            $startValue = 1013.25; 
            $maxChange = 5;
            break;
        default:
            $startValue = 50;
            $maxChange = 10;
    }
    for ($i = 24; $i >= 0; $i--) {
        $timeStamp = $now - ($i * 3600); 
        $randomFactor = mt_rand(-100, 100) / 100; 
        $variation = $randomFactor * $maxChange;
        $hourOfDay = (int)date('H', $timeStamp);
        $dayNightVariation = 0;
        switch ($type) {
            case 'temperature':
                $dayNightVariation = ($hourOfDay >= 8 && $hourOfDay <= 18) ? 1.5 : -1;
                break;
            case 'humidity':
                $dayNightVariation = ($hourOfDay >= 8 && $hourOfDay <= 18) ? -5 : 3;
                break;
            case 'co2':
                $dayNightVariation = ($hourOfDay >= 10 && $hourOfDay <= 20) ? 100 : -50;
                break;
            case 'soil_moisture':
                $dayNightVariation = ($hourOfDay >= 9 && $hourOfDay <= 20) ? -3 : 2;
                if ($hourOfDay == 7) $dayNightVariation = 10; 
                break;
            case 'light_level':
                if ($hourOfDay >= 8 && $hourOfDay <= 17) {
                    $hourFromNoon = abs(12 - $hourOfDay);
                    $dayNightVariation = 8000 - ($hourFromNoon * 1000);
                } else {
                    $dayNightVariation = -4500;
                }
                break;
            case 'pressure':
                $dayNightVariation = sin($hourOfDay/24 * 2 * M_PI) * 2;
                break;
        }
        $value = $startValue + $variation + $dayNightVariation;
        switch ($type) {
            case 'temperature':
                $value = max(15, min(35, $value)); 
                break;
            case 'humidity':
                $value = max(30, min(80, $value)); 
                break;
            case 'co2':
                $value = max(400, min(2000, $value)); 
                break;
            case 'soil_moisture':
                $value = max(20, min(90, $value)); 
                break;
            case 'light_level':
                $value = max(0, min(10000, $value)); 
                break;
            case 'pressure':
                $value = max(980, min(1040, $value)); 
                break;
        }
        $data[] = [
            'time' => date('H:i', $timeStamp),
            'value' => round($value, 1)
        ];
    }
    return $data;
}
function generateDemoDataForWeek($type) {
    $data = [];
    $now = time();
    $startValue = 0;
    $maxChange = 0;
    switch ($type) {
        case 'temperature':
            $startValue = 23.5;
            $maxChange = 3.5;
            break;
        case 'humidity':
            $startValue = 55;
            $maxChange = 15;
            break;
        case 'co2':
            $startValue = 800;
            $maxChange = 250;
            break;
        case 'soil_moisture':
            $startValue = 65;
            $maxChange = 10;
            break;
        case 'light_level':
            $startValue = 5000;
            $maxChange = 3000;
            break;
        case 'pressure':
            $startValue = 1013.25; 
            $maxChange = 8;
            break;
        default:
            $startValue = 50;
            $maxChange = 10;
    }
    for ($i = 42; $i >= 0; $i--) {
        $timeStamp = $now - ($i * 14400); 
        $randomFactor = mt_rand(-100, 100) / 100; 
        $variation = $randomFactor * $maxChange;
        $dayOfWeek = (int)date('w', $timeStamp);
        $hourOfDay = (int)date('H', $timeStamp);
        $dayVariation = 0;
        switch ($type) {
            case 'temperature':
                $dayVariation = $dayOfWeek * 0.3;
                $dayVariation += ($hourOfDay >= 8 && $hourOfDay <= 18) ? 1 : -1.5;
                break;
            case 'humidity':
                $dayVariation = $dayOfWeek * -1;
                $dayVariation += ($hourOfDay >= 8 && $hourOfDay <= 18) ? -3 : 5;
                break;
            case 'co2':
                $dayVariation = $dayOfWeek * 30; 
                $dayVariation += ($hourOfDay >= 10 && $hourOfDay <= 20) ? 100 : -50;
                break;
            case 'soil_moisture':
                $dayVariation = $dayOfWeek * -0.5;
                if ($hourOfDay == 7) $dayVariation += 10; 
                break;
            case 'light_level':
                $dayVariation = $dayOfWeek * 200;
                if ($hourOfDay >= 8 && $hourOfDay <= 17) {
                    $hourFromNoon = abs(12 - $hourOfDay);
                    $dayVariation += 8000 - ($hourFromNoon * 1000);
                } else {
                    $dayVariation += -4500;
                }
                break;
            case 'pressure':
                $dayVariation = sin($dayOfWeek/7 * 2 * M_PI) * 4 + sin($hourOfDay/24 * 2 * M_PI) * 2;
                break;
        }
        $value = $startValue + $variation + $dayVariation;
        switch ($type) {
            case 'temperature':
                $value = max(15, min(35, $value)); 
                break;
            case 'humidity':
                $value = max(30, min(80, $value)); 
                break;
            case 'co2':
                $value = max(400, min(2000, $value)); 
                break;
            case 'soil_moisture':
                $value = max(20, min(90, $value)); 
                break;
            case 'light_level':
                $value = max(0, min(10000, $value)); 
                break;
            case 'pressure':
                $value = max(980, min(1040, $value)); 
                break;
        }
        $data[] = [
            'datetime' => date('Y-m-d H:i', $timeStamp),
            'value' => round($value, 1)
        ];
    }
    return $data;
}
function getDayData($pdo, $user_id, $type) {
    global $isGuest;
    if ($isGuest) {
        return generateDemoDataForDay($type);
    }
    $validColumns = ['temperature', 'humidity', 'soil_moisture', 'light_level', 'co2', 'pressure'];
    if (!in_array($type, $validColumns)) {
        return []; 
    }
    $query = "SELECT 
                DATE_FORMAT(created_at, '%H:%i') as time,
                ROUND(AVG($type), 2) as value
             FROM sensor_data 
             WHERE user_id = ? 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
             AND $type IS NOT NULL
             GROUP BY HOUR(created_at), MINUTE(created_at)
             ORDER BY created_at";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Ошибка при получении данных за день: " . $e->getMessage());
        return [];
    }
}
function getWeekData($pdo, $user_id, $type) {
    global $isGuest;
    if ($isGuest) {
        return generateDemoDataForWeek($type);
    }
    $validColumns = ['temperature', 'humidity', 'soil_moisture', 'light_level', 'co2', 'pressure'];
    if (!in_array($type, $validColumns)) {
        return []; 
    }
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as datetime,
                ROUND(AVG($type), 2) as value
             FROM sensor_data 
             WHERE user_id = ? 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             AND $type IS NOT NULL
             GROUP BY DATE(created_at), HOUR(created_at)
             ORDER BY created_at";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Ошибка при получении данных за неделю: " . $e->getMessage());
        return [];
    }
}
?>
<div class="graphs-header" onclick="toggleGraphs()">
    <h2>Графики</h2>
    <div class="header-right-content">
        <span class="accordion-icon">▼</span>
    </div>
</div>
<div class="graphs-content" id="graphsContent">
    <!-- Уведомление для гостей -->
    <?php if ($isGuest): ?>
    <div class="guest-notice-container">
        <div class="guest-notice">
            <p>Вы просматриваете демонстрационные данные. Для доступа к вашим данным необходимо <a href="authentication/login.php">авторизоваться</a>.</p>
        </div>
    </div>
    <?php endif; ?>
    <!-- Убираем статичный блок данных и добавим его динамически через JavaScript -->
    <div class="data-info-container" id="dataInfoContainer" style="display: none;">
        <div class="data-info">
            <p>Пустой график означает отсутствие данных за выбранный период.</p>
        </div>
    </div>
    <!-- Переключатели графиков -->
    <div class="graph-controls">
        <div class="graph-type-selector">
            <button class="graph-btn active" data-type="temperature">Температура</button>
            <button class="graph-btn" data-type="humidity">Влажность</button>
            <button class="graph-btn" data-type="soil_moisture">Влажность почвы</button>
            <button class="graph-btn" data-type="light_level">Свет</button>
            <button class="graph-btn" data-type="co2">CO₂</button>
            <button class="graph-btn" data-type="pressure">Давление</button>
        </div>
        <div class="period-selector">
            <button class="period-btn active" data-period="day">За день</button>
            <button class="period-btn" data-period="week">За неделю</button>
        </div>
    </div>
    <!-- Контейнеры для графиков -->
    <div class="graph-display">
        <canvas id="farmChart"></canvas>
    </div>
    <!-- Область для отображения ошибок -->
    <div class="error-display" style="display: none;">
        <h4>Обнаруженные проблемы:</h4>
        <ul id="errorList"></ul>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dayData = {
    temperature: <?php echo json_encode(getDayData($pdo, $user_id, 'temperature')); ?>,
    humidity: <?php echo json_encode(getDayData($pdo, $user_id, 'humidity')); ?>,
    soil_moisture: <?php echo json_encode(getDayData($pdo, $user_id, 'soil_moisture')); ?>,
    light_level: <?php echo json_encode(getDayData($pdo, $user_id, 'light_level')); ?>,
    co2: <?php echo json_encode(getDayData($pdo, $user_id, 'co2')); ?>,
    pressure: <?php echo json_encode(getDayData($pdo, $user_id, 'pressure')); ?>
};
const weekData = {
    temperature: <?php echo json_encode(getWeekData($pdo, $user_id, 'temperature')); ?>,
    humidity: <?php echo json_encode(getWeekData($pdo, $user_id, 'humidity')); ?>,
    soil_moisture: <?php echo json_encode(getWeekData($pdo, $user_id, 'soil_moisture')); ?>,
    light_level: <?php echo json_encode(getWeekData($pdo, $user_id, 'light_level')); ?>,
    co2: <?php echo json_encode(getWeekData($pdo, $user_id, 'co2')); ?>,
    pressure: <?php echo json_encode(getWeekData($pdo, $user_id, 'pressure')); ?>
};
const chartConfig = {
    temperature: {
        label: 'Температура',
        unit: '°C',
        borderColor: 'rgb(255, 99, 132)',
        backgroundColor: 'rgba(255, 99, 132, 0.2)'
    },
    humidity: {
        label: 'Влажность',
        unit: '%',
        borderColor: 'rgb(54, 162, 235)',
        backgroundColor: 'rgba(54, 162, 235, 0.2)'
    },
    soil_moisture: {
        label: 'Влажность почвы',
        unit: '%',
        borderColor: 'rgb(139, 69, 19)',
        backgroundColor: 'rgba(139, 69, 19, 0.2)'
    },
    light_level: {
        label: 'Освещенность',
        unit: 'люкс',
        borderColor: 'rgb(255, 206, 86)',
        backgroundColor: 'rgba(255, 206, 86, 0.2)'
    },
    co2: {
        label: 'CO₂',
        unit: 'ppm',
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)'
    },
    pressure: {
        label: 'Давление',
        unit: 'гПа',
        borderColor: 'rgb(153, 102, 255)',
        backgroundColor: 'rgba(153, 102, 255, 0.2)'
    }
};
let currentChart = null;
let currentType = 'temperature';
let currentPeriod = 'day';
function updateChart() {
    const ctx = document.getElementById('farmChart').getContext('2d');
    const config = chartConfig[currentType];
    const data = currentPeriod === 'day' ? dayData[currentType] : weekData[currentType];
    const isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark';
    const dataInfoContainer = document.getElementById('dataInfoContainer');
    if (currentChart) {
        currentChart.destroy();
    }
    if (!data || data.length === 0) {
        const errorDisplay = document.querySelector('.error-display');
        const errorList = document.getElementById('errorList');
        errorList.innerHTML = '<li>Нет данных для выбранного периода</li>';
        errorDisplay.style.display = 'block';
        dataInfoContainer.style.display = 'block';
        currentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Нет данных'],
                datasets: [{
                    label: config.label,
                    data: [],
                    backgroundColor: 'rgba(200, 200, 200, 0.2)',
                    borderColor: 'rgba(200, 200, 200, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: `${config.label} ${currentPeriod === 'day' ? 'за день' : 'за неделю'} - Данные отсутствуют`,
                        color: isDarkTheme ? '#fff' : '#666',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });
        return;
    } else {
        dataInfoContainer.style.display = 'none';
    }
    let chartData;
    if (currentPeriod === 'day') {
        chartData = {
            labels: data.map(item => item.time),
            datasets: [{
                label: config.label,
                data: data.map(item => item.value),
                borderColor: config.borderColor,
                backgroundColor: 'transparent',
                tension: 0.4,
                fill: false
            }]
        };
    } else {
        chartData = {
            labels: data.map(item => item.datetime),
            datasets: [{
                label: config.label,
                data: data.map(item => item.value),
                borderColor: config.borderColor,
                backgroundColor: 'transparent',
                tension: 0.4,
                fill: false
            }]
        };
    }
    currentChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: `${config.label} ${currentPeriod === 'day' ? 'за день' : 'за неделю'}`,
                    color: isDarkTheme ? '#fff' : '#666',
                    font: {
                        size: 16
                    }
                },
                legend: {
                    display: currentPeriod === 'day',
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        color: isDarkTheme ? '#fff' : '#666',
                        font: {
                            size: 12
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        color: isDarkTheme ? '#fff' : '#666'
                    },
                    title: {
                        display: true,
                        text: config.unit,
                        color: isDarkTheme ? '#fff' : '#666'
                    }
                },
                x: {
                    grid: {
                        color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        color: isDarkTheme ? '#fff' : '#666',
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            elements: {
                point: {
                    radius: 3,
                    hoverRadius: 5
                },
                line: {
                    tension: 0.4
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
    updateErrors(data);
}
function updateErrors(data) {
    const errorList = document.getElementById('errorList');
    const errorDisplay = document.querySelector('.error-display');
    const limits = {
        temperature: { min: 15.00, max: 30.00 },
        humidity: { min: 40.00, max: 60.00 },
        soil_moisture: { min: 30.00, max: 80.00 },
        co2: { min: 600.00, max: 2000.00 }
    };
    const errors = new Set();
    data.forEach(item => {
        const value = parseFloat(item.value);
        switch(currentType) {
            case 'temperature':
                if (value < limits.temperature.min || value > limits.temperature.max) {
                    errors.add(`Температура ${value}°C выходит за пределы (${limits.temperature.min}°C - ${limits.temperature.max}°C)`);
                }
                break;
            case 'humidity':
                if (value < limits.humidity.min || value > limits.humidity.max) {
                    errors.add(`Влажность ${value}% выходит за пределы (${limits.humidity.min}% - ${limits.humidity.max}%)`);
                }
                break;
            case 'soil_moisture':
                if (value < limits.soil_moisture.min || value > limits.soil_moisture.max) {
                    errors.add(`Влажность почвы ${value}% выходит за пределы (${limits.soil_moisture.min}% - ${limits.soil_moisture.max}%)`);
                }
                break;
            case 'co2':
                if (value < limits.co2.min || value > limits.co2.max) {
                    errors.add(`CO₂ ${value}ppm выходит за пределы (${limits.co2.min}ppm - ${limits.co2.max}ppm)`);
                }
                break;
        }
    });
    errorList.innerHTML = '';
    if (errors.size > 0) {
        errors.forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            errorList.appendChild(li);
        });
        errorDisplay.style.display = 'block';
    } else {
        errorDisplay.style.display = 'none';
    }
}
function shiftColor(color, degree) {
    if (color.startsWith('rgb')) {
        const matches = color.match(/\d+/g);
        return `rgba(${matches[0]}, ${matches[1]}, ${matches[2]}, ${0.6 - degree/360})`;
    }
    return color;
}
document.querySelectorAll('.graph-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelector('.graph-btn.active').classList.remove('active');
        this.classList.add('active');
        currentType = this.dataset.type;
        updateChart();
    });
});
document.querySelectorAll('.period-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelector('.period-btn.active').classList.remove('active');
        this.classList.add('active');
        currentPeriod = this.dataset.period;
        updateChart();
    });
});
function handleDataErrors() {
    console.log('Checking data availability...');
    return true;
}
updateChart();
document.addEventListener('themeChanged', function(e) {
    if (currentChart) {
        updateChart();
    }
});
function toggleGraphs() {
    const content = document.getElementById('graphsContent');
    const icon = document.querySelector('.graphs-header .accordion-icon');
    content.classList.toggle('active');
    icon.classList.toggle('rotate');
}
document.addEventListener('DOMContentLoaded', function() {
    const graphsContent = document.getElementById('graphsContent');
    const graphsIcon = document.querySelector('.graphs-header .accordion-icon');
    if (graphsContent && graphsIcon) {
    }
});
</script>
<style>
.graphs-container {
    padding: 20px;
    background: var(--card-bg);
    border-radius: 10px;
    margin: 20px 0;
}
[data-theme="dark"] .graphs-container {
    background: var(--dark-card-bg, #2a2a2a);
}
[data-theme="dark"] .graph-display {
    background: var(--dark-bg, #1a1a1a);
}
[data-theme="dark"] .graph-btn,
[data-theme="dark"] .period-btn {
    background: var(--dark-button-bg, #333);
    color: var(--dark-text, #fff);
    border-color: var(--dark-border, #444);
}
[data-theme="dark"] .graph-btn:hover,
[data-theme="dark"] .period-btn:hover {
    background: var(--dark-hover, #444);
}
[data-theme="dark"] .graph-btn.active,
[data-theme="dark"] .period-btn.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
}
.graph-controls {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}
.graph-type-selector,
.period-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.graph-btn,
.period-btn {
    padding: 8px 16px;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    background: var(--bg-color);
    color: var(--text-color);
    cursor: pointer;
    transition: all 0.3s ease;
    flex-grow: 1;
    white-space: nowrap;
    text-align: center;
    margin: 0;
    font-size: 14px;
}
.graph-btn:hover,
.period-btn:hover {
    background: var(--hover-color);
}
.graph-btn.active,
.period-btn.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}
@media (min-width: 768px) {
    .graph-controls {
        flex-direction: row;
        justify-content: space-between;
    }
    .graph-btn,
    .period-btn {
        flex-grow: 0;
        font-size: 16px;
    }
    .graph-type-selector {
        max-width: 70%;
    }
    .period-selector {
        max-width: 30%;
    }
}
.graph-display {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    height: 400px;
}
.error-display {
    background: var(--error-bg);
    border: 1px solid var(--error-border);
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}
.error-display h4 {
    color: var(--error-text);
    margin: 0 0 10px 0;
}
#errorList {
    margin: 0;
    padding-left: 20px;
    color: var(--error-text);
}
#errorList li {
    margin-bottom: 5px;
}
/* Стили для аккордеона */
.graphs-content {
    display: none;
    padding: 20px;
    background: var(--card-bg);
    margin-top: 0;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.graphs-content.active {
    display: block;
}
.graphs-header {
    margin-top: 20px;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    border-radius: 8px 8px 0 0;
    background: var(--primary-color);
    color: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.graphs-header h2 {
    margin: 0;
    font-size: 18px;
}
.accordion-icon {
    transition: transform 0.3s ease;
}
.accordion-icon.rotate {
    transform: rotate(180deg);
}
/* Стили для уведомления гостя */
.guest-notice-container {
    width: 100%;
    margin-bottom: 20px;
    box-sizing: border-box;
}
.guest-notice {
    background-color: rgba(255, 193, 7, 0.2);
    border-left: 4px solid #ffc107;
    padding: 10px 15px;
    border-radius: 4px;
    width: 100%;
    box-sizing: border-box;
}
.guest-notice p {
    margin: 0;
    color: var(--text-color, #555);
    white-space: normal;
    overflow-wrap: break-word;
    word-wrap: break-word;
}
.guest-notice a {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}
.guest-notice a:hover {
    text-decoration: underline;
}
/* Остальные стили */
.data-info-container {
    width: 100%;
    margin-bottom: 20px;
    box-sizing: border-box;
}
.data-info {
    background-color: rgba(13, 110, 253, 0.1);
    border-left: 4px solid #0d6efd;
    padding: 10px 15px;
    border-radius: 4px;
    width: 100%;
    box-sizing: border-box;
}
.data-info p {
    margin: 0;
    color: var(--text-color, #333);
    white-space: normal;
    overflow-wrap: break-word;
    word-wrap: break-word;
}
</style> 