<?php
require_once 'config/database.php';
$isGuest = !isset($_SESSION['user_id']);
$user_id = $isGuest ? 1 : $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'alarm_thresholds'
");
$stmt->execute();
$tableExists = (bool) $stmt->fetchColumn();
if (!$tableExists) {
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
if (!isset($thresholds['temperature'])) {
    $thresholds['temperature'] = [
        'min_limit' => 20.00,
        'max_limit' => 30.00,
        'target_value' => 25.00,
        'tolerance' => 2.00
    ];
}
if (!isset($thresholds['humidity_air'])) {
    $thresholds['humidity_air'] = [
        'min_limit' => 50.00,
        'max_limit' => 70.00,
        'target_value' => 60.00,
        'tolerance' => 5.00
    ];
}
if (!isset($thresholds['humidity_soil'])) {
    $thresholds['humidity_soil'] = [
        'min_limit' => 30.00,
        'max_limit' => 70.00,
        'target_value' => 50.00,
        'tolerance' => 5.00
    ];
}
if (!isset($thresholds['co2'])) {
    $thresholds['co2'] = [
        'min_limit' => 200.00,
        'max_limit' => 1000.00,
        'target_value' => 600.00,
        'tolerance' => 100.00
    ];
}
function formatNumberNoCommas($number) {
    return round($number);
}
?>
<div class="graphs-header" id="alarmHeader" onclick="toggleAlarmContent()">
    <h2>Пороги тревоги</h2>
    <div class="header-right-content">
        <span class="accordion-icon" id="alarmIcon">▼</span>
    </div>
</div>
<div class="graphs-content" id="alarmContent" itemscope itemtype="https://schema.org/ControlAction">
    <meta itemprop="name" content="Настройка порогов тревоги">
    <meta itemprop="description" content="Настройка допустимых пределов параметров микроклимата для умной фермы">
    <meta itemprop="controlledProperty" content="EnvironmentalConditions">
    <?php if ($isGuest): ?>
    <div class="guest-notice">
        <p>Настройка порогов тревог доступна только для авторизованных пользователей. Пожалуйста, <a href="authentication/login.php">авторизуйтесь</a> для доступа.</p>
    </div>
    <?php else: ?>
    <form id="limits-form" class="alarm-settings-form" itemprop="potentialAction" itemscope itemtype="https://schema.org/UpdateAction">
        <meta itemprop="target" content="api/save-limits.php">
        <meta itemprop="actionStatus" content="PotentialActionStatus">
        <div class="form-group" itemprop="object" itemscope itemtype="https://schema.org/PropertyValue">
            <meta itemprop="propertyID" content="temperature">
            <h3 itemprop="name">Температура воздуха (°C)</h3>
            <p class="current-limits">Текущие пороги: <span itemprop="minValue"><?php echo formatNumberNoCommas($thresholds['temperature']['min_limit']); ?></span> - <span itemprop="maxValue"><?php echo formatNumberNoCommas($thresholds['temperature']['max_limit']); ?></span></p>
            <div class="input-row">
                <div class="input-group">
                    <label for="temp-min">Минимум:</label>
                    <input type="text" id="temp-min" name="temp-min" pattern="[0-9]+" value="<?php echo formatNumberNoCommas($thresholds['temperature']['min_limit']); ?>" required title="Минимальное значение температуры" aria-label="Минимальное значение температуры" placeholder="Введите минимум">
                </div>
                <div class="input-group">
                    <label for="temp-max">Максимум:</label>
                    <input type="text" id="temp-max" name="temp-max" pattern="[0-9]+" value="<?php echo formatNumberNoCommas($thresholds['temperature']['max_limit']); ?>" required title="Максимальное значение температуры" aria-label="Максимальное значение температуры" placeholder="Введите максимум">
                </div>
            </div>
            <meta itemprop="unitCode" content="CEL">
        </div>
        <div class="form-group" itemprop="object" itemscope itemtype="https://schema.org/PropertyValue">
            <meta itemprop="propertyID" content="humidity_air">
            <h3 itemprop="name">Влажность воздуха (%)</h3>
            <p class="current-limits">Текущие пороги: <span itemprop="minValue"><?php echo formatNumberNoCommas($thresholds['humidity_air']['min_limit']); ?></span> - <span itemprop="maxValue"><?php echo formatNumberNoCommas($thresholds['humidity_air']['max_limit']); ?></span></p>
            <div class="input-row">
                <div class="input-group">
                    <label for="humidity-min">Минимум:</label>
                    <input type="text" id="humidity-min" name="humidity-min" pattern="[0-9]+" value="<?php echo formatNumberNoCommas($thresholds['humidity_air']['min_limit']); ?>" required title="Минимальное значение влажности воздуха" aria-label="Минимальное значение влажности воздуха" placeholder="Введите минимум">
                </div>
                <div class="input-group">
                    <label for="humidity-max">Максимум:</label>
                    <input type="text" id="humidity-max" name="humidity-max" pattern="[0-9]+" value="<?php echo formatNumberNoCommas($thresholds['humidity_air']['max_limit']); ?>" required title="Максимальное значение влажности воздуха" aria-label="Максимальное значение влажности воздуха" placeholder="Введите максимум">
                </div>
            </div>
            <meta itemprop="unitCode" content="P1">
        </div>
        <div class="form-group" itemprop="object" itemscope itemtype="https://schema.org/PropertyValue">
            <meta itemprop="propertyID" content="humidity_soil">
            <h3 itemprop="name">Влажность почвы (%)</h3>
            <p class="current-limits">Текущие пороги: <span itemprop="minValue"><?php echo formatNumberNoCommas($thresholds['humidity_soil']['min_limit']); ?></span> - <span itemprop="maxValue"><?php echo formatNumberNoCommas($thresholds['humidity_soil']['max_limit']); ?></span></p>
            <div class="input-row">
                <div class="input-group">
                    <label for="soil-moisture-min">Минимум:</label>
                    <input type="text" id="soil-moisture-min" name="soil-moisture-min" pattern="[0-9]+" value="<?php echo formatNumberNoCommas($thresholds['humidity_soil']['min_limit']); ?>" required title="Минимальное значение влажности почвы" aria-label="Минимальное значение влажности почвы" placeholder="Введите минимум">
                </div>
                <div class="input-group">
                    <label for="soil-moisture-max">Максимум:</label>
                    <input type="text" id="soil-moisture-max" name="soil-moisture-max" pattern="[0-9]+" value="<?php echo formatNumberNoCommas($thresholds['humidity_soil']['max_limit']); ?>" required title="Максимальное значение влажности почвы" aria-label="Максимальное значение влажности почвы" placeholder="Введите максимум">
                </div>
            </div>
            <meta itemprop="unitCode" content="P1">
        </div>
        <div class="form-group" itemprop="object" itemscope itemtype="https://schema.org/PropertyValue">
            <meta itemprop="propertyID" content="co2">
            <h3 itemprop="name">CO2 (ppm)</h3>
            <p class="current-limits">Текущие пороги: <span itemprop="minValue"><?php echo formatNumberNoCommas($thresholds['co2']['min_limit']); ?></span> - <span itemprop="maxValue"><?php echo formatNumberNoCommas($thresholds['co2']['max_limit']); ?></span></p>
            <div class="input-row">
                <div class="input-group">
                    <label for="co2-min">Минимум:</label>
                    <input type="text" id="co2-min" name="co2-min" pattern="[0-9]+" value="<?php echo formatNumberNoCommas($thresholds['co2']['min_limit']); ?>" required title="Минимальное значение CO2" aria-label="Минимальное значение CO2" placeholder="Введите минимум">
                </div>
                <div class="input-group">
                    <label for="co2-max">Максимум:</label>
                    <input type="text" id="co2-max" name="co2-max" pattern="[0-9]+" value="<?php echo formatNumberNoCommas($thresholds['co2']['max_limit']); ?>" required title="Максимальное значение CO2" aria-label="Максимальное значение CO2" placeholder="Введите максимум">
                </div>
            </div>
            <meta itemprop="unitCode" content="PPM">
        </div>
        <button type="submit" class="btn btn-save" itemprop="instrument">Сохранить</button>
        <meta itemprop="dateModified" content="<?php echo date('c'); ?>">
    </form>
    <?php endif; ?>
</div>
<style>
.graphs-content{display:none;padding:20px;background:var(--card-bg);margin-top:20px;border-radius:8px}
.graphs-content.active{display:block}
.graphs-header{margin-top:20px}
.accordion-icon{transition:transform 0.3s ease}
.accordion-icon.rotate{transform:rotate(180deg)}
.form-group{margin-bottom:20px}
.form-group h3{margin:0 0 10px 0;font-size:16px;color:var(--text-color,#fff)}
.input-row{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}
.input-group{flex:1;min-width:140px}
.input-group label{display:block;margin-bottom:5px;color:var(--text-color,#fff)}
.input-group input{width:100%;padding:8px;border:1px solid var(--border-color,#444);border-radius:4px;background:var(--input-bg,#333);color:var(--text-color,#fff)}
[data-theme="light"] .input-group input{background:var(--input-bg,#fff);color:var(--text-color,#000);border-color:var(--border-color,#ddd)}
.current-limits{margin:5px 0;color:var(--secondary-text,#888)}
.btn-save{background-color:#4CAF50;color:white;padding:12px 24px;border:none;border-radius:8px;font-size:16px;cursor:pointer;margin-top:20px;width:200px;float:right}
.guest-notice{background-color:rgba(255,193,7,0.2);border-left:4px solid #ffc107;padding:15px;margin:10px 0;border-radius:4px;text-align:center}
.guest-notice p{margin:0;color:var(--text-color,#555);font-size:16px}
.guest-notice a{color:#007bff;text-decoration:none;font-weight:bold}
.guest-notice a:hover{text-decoration:underline}
button[disabled]{background-color:#cccccc !important;cursor:not-allowed !important}
@media (max-width:480px){.input-row{flex-direction:column;gap:10px}.input-group{width:100%}.form-group{margin-bottom:30px}.btn-save{width:100%;margin-top:30px}.current-limits{font-size:0.9rem;margin-bottom:15px}}
.form-group h3{font-size:1.1rem;margin-bottom:15px;font-weight:600}
.input-group label{font-size:0.95rem;margin-bottom:8px}
.input-group input{font-size:1rem;padding:10px}
.current-limits{font-size:0.9rem;opacity:0.8;margin:10px 0}
.btn-save{transition:background-color 0.2s ease;font-weight:500}
.btn-save:hover:not([disabled]){background-color:#45a049}
.btn-save:active:not([disabled]){transform:translateY(1px)}
[data-theme="dark"] .input-group input{background:#2A2A2A;border-color:#3A3A3A}
[data-theme="dark"] .current-limits{color:#B0B0B0}
</style>
<script>
document.getElementById('limits-form')?.addEventListener('submit',async function(e){
    e.preventDefault();
    const tempMinInput=document.getElementById('temp-min').value;
    const tempMaxInput=document.getElementById('temp-max').value;
    const humidityMinInput=document.getElementById('humidity-min').value;
    const humidityMaxInput=document.getElementById('humidity-max').value;
    const soilMoistureMinInput=document.getElementById('soil-moisture-min').value;
    const soilMoistureMaxInput=document.getElementById('soil-moisture-max').value;
    const co2MinInput=document.getElementById('co2-min').value;
    const co2MaxInput=document.getElementById('co2-max').value;
    if(!/^\d+$/.test(tempMinInput)||!/^\d+$/.test(tempMaxInput)||!/^\d+$/.test(humidityMinInput)||!/^\d+$/.test(humidityMaxInput)||!/^\d+$/.test(soilMoistureMinInput)||!/^\d+$/.test(soilMoistureMaxInput)||!/^\d+$/.test(co2MinInput)||!/^\d+$/.test(co2MaxInput)){
        alert('Пожалуйста, введите только целые числа без дробных частей, букв и специальных символов');
        return;
    }
    const tempMin=parseInt(tempMinInput);
    const tempMax=parseInt(tempMaxInput);
    const humidityMin=parseInt(humidityMinInput);
    const humidityMax=parseInt(humidityMaxInput);
    const soilMoistureMin=parseInt(soilMoistureMinInput);
    const soilMoistureMax=parseInt(soilMoistureMaxInput);
    const co2Min=parseInt(co2MinInput);
    const co2Max=parseInt(co2MaxInput);
    if(tempMin>=tempMax){
        alert('Минимальная температура должна быть меньше максимальной');
        return;
    }
    if(humidityMin>=humidityMax){
        alert('Минимальная влажность воздуха должна быть меньше максимальной');
        return;
    }
    if(soilMoistureMin>=soilMoistureMax){
        alert('Минимальная влажность почвы должна быть меньше максимальной');
        return;
    }
    if(co2Min>=co2Max){
        alert('Минимальный уровень CO2 должен быть меньше максимального');
        return;
    }
    const data={
        temperature:{min:tempMin,max:tempMax},
        humidity:{min:humidityMin,max:humidityMax},
        soil_moisture:{min:soilMoistureMin,max:soilMoistureMax},
        co2:{min:co2Min,max:co2Max}
    };
    try{
        const response=await fetch('api/save-limits.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify(data)
        });
        const result=await response.json();
        if(result.success){
            alert('Настройки сохранены');
            document.querySelectorAll('.current-limits').forEach(function(el,index){
                if(index===0){
                    el.textContent='Текущие пороги: '+tempMin+' - '+tempMax;
                }else if(index===1){
                    el.textContent='Текущие пороги: '+humidityMin+' - '+humidityMax;
                }else if(index===2){
                    el.textContent='Текущие пороги: '+soilMoistureMin+' - '+soilMoistureMax;
                }else if(index===3){
                    el.textContent='Текущие пороги: '+co2Min+' - '+co2Max;
                }
            });
        }else{
            alert('Ошибка при сохранении: '+(result.error||'Неизвестная ошибка'));
            console.error(result.error);
        }
    }catch(error){
        alert('Ошибка при сохранении');
        console.error('Error:',error);
    }
});
document.addEventListener('DOMContentLoaded',function(){
    const numberInputs=document.querySelectorAll('input[pattern="[0-9]+"]');
    numberInputs.forEach(function(input){
        input.addEventListener('input',function(e){
            this.value=this.value.replace(/[^\d]/g,'');
        });
        input.addEventListener('paste',function(e){
            const pastedText=(e.clipboardData||window.clipboardData).getData('text');
            const cleanedText=pastedText.replace(/[^\d]/g,'');
            e.preventDefault();
            this.value=cleanedText;
        });
    });
    const farmSettingsContent=document.getElementById('farmSettingsContent');
    if(farmSettingsContent){
        const farmSettingsIcon=document.querySelector('.farm-settings-header .accordion-icon');
        function checkFarmSettingsState(){
            if(farmSettingsContent.classList.contains('active')){
                farmSettingsIcon.classList.add('rotate');
            }else{
                farmSettingsIcon.classList.remove('rotate');
            }
        }
        checkFarmSettingsState();
        const observer=new MutationObserver(function(mutations){
            mutations.forEach(function(mutation){
                if(mutation.attributeName==='class'){
                    checkFarmSettingsState();
                }
            });
        });
        observer.observe(farmSettingsContent,{attributes:true});
    }
});
function toggleAlarmContent(){
    const content=document.getElementById('alarmContent');
    const icon=document.getElementById('alarmIcon');
    content.classList.toggle('active');
    icon.classList.toggle('rotate');
}
</script>