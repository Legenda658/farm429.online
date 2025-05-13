<?php
require_once 'config/database.php';
$user_id = $_SESSION['user_id'] ?? null;
$presetModes = [];
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM preset_modes WHERE user_id = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $presetModes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
if (empty($presetModes) && $user_id) {
   $defaultModes = [
        ['name' => 'Томат (Вегетация)', 'temperature' => 24.0, 'tolerance' => 2.0, 'humidity' => 65, 'humidity_tolerance' => 5.0, 'light_hours' => 16.0, 'light_start' => '06:00', 'light_end' => '22:00'],
        ['name' => 'Огурец (Плодоношение)', 'temperature' => 22.0, 'tolerance' => 1.5, 'humidity' => 75, 'humidity_tolerance' => 5.0, 'light_hours' => 14.0, 'light_start' => '07:00', 'light_end' => '21:00'],
        ['name' => 'Салат (Рост)', 'temperature' => 20.0, 'tolerance' => 2.0, 'humidity' => 70, 'humidity_tolerance' => 5.0, 'light_hours' => 12.0, 'light_start' => '08:00', 'light_end' => '20:00'],
   ];
   $insertStmt = $pdo->prepare("
        INSERT INTO preset_modes (user_id, name, temperature, tolerance, humidity, humidity_tolerance, light_hours, light_start, light_end) 
        VALUES (:user_id, :name, :temperature, :tolerance, :humidity, :humidity_tolerance, :light_hours, :light_start, :light_end)
   ");
   foreach ($defaultModes as $mode) {
       $insertStmt->execute(array_merge([':user_id' => $user_id], $mode));
   }
   $stmt->execute([$user_id]);
   $presetModes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="graphs-header" id="presetModesHeader">
    <h2>Предустановленные режимы 🌱</h2>
    <div class="header-right-content">
        <span class="accordion-icon" id="presetModesIcon">▼</span>
    </div>
</div>
<div class="graphs-content" id="presetModesContent">
    <div class="preset-header d-flex justify-content-center mb-4">
        <div class="import-code-container">
            <h4>Импортировать режим</h4>
            <div class="input-group">
                <input type="text" id="importCodeInput" class="form-control form-control-lg" placeholder="Введите код режима" maxlength="8">
                <button class="btn btn-success rounded-3 import-btn" id="importCodeBtn">Загрузить код</button>
            </div>
        </div>
    </div>
    <div class="preset-modes-container">
        <?php if (!empty($presetModes)): ?>
            <?php foreach ($presetModes as $mode): ?>
            <div class="preset-mode" data-id="<?php echo $mode['id']; ?>">
                <h3><?php echo htmlspecialchars($mode['name']); ?></h3>
                <div class="preset-params">
                    <div class="preset-param">
                        <span class="param-icon">🌡️</span>
                        <span class="param-label">Темп.:</span>
                        <span class="param-value"><?php echo number_format($mode['temperature'], 1, '.', ''); ?>°C (±<?php echo $mode['tolerance']; ?>°C)</span>
                    </div>
                    <div class="preset-param">
                        <span class="param-icon">💧</span>
                        <span class="param-label">Влаж.:</span>
                        <span class="param-value"><?php echo $mode['humidity']; ?>% (±<?php echo $mode['humidity_tolerance']; ?>%)</span>
                    </div>
                    <div class="preset-param">
                        <span class="param-icon">☀️</span>
                        <span class="param-label">Свет:</span>
                        <span class="param-value"><?php echo number_format($mode['light_hours'], 1, '.', ''); ?> ч (<?php echo substr($mode['light_start'], 0, 5); ?> - <?php echo substr($mode['light_end'], 0, 5); ?>)</span>
                    </div>
                </div>
                <div class="preset-actions">
                    <button class="activate-button" data-mode='<?php echo htmlspecialchars(json_encode($mode), ENT_QUOTES, 'UTF-8'); ?>'>Активировать</button>
                    <button class="share-button" type="button" data-id="<?php echo $mode['id']; ?>" data-name="<?php echo htmlspecialchars($mode['name']); ?>">🔗</button>
                    <button class="delete-button" type="button" data-id="<?php echo $mode['id']; ?>" data-name="<?php echo htmlspecialchars($mode['name']); ?>">🗑️</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
             <p>У вас пока нет сохраненных режимов.</p>
        <?php endif; ?>
        <button class="add-preset-button" id="addPresetButton">+ Добавить новый режим</button>
    </div>
</div>
<!-- Модальное окно добавления режима -->
<div id="addPresetModal" class="modal">
    <div class="modal-content">
        <span class="close-button" id="closeModalButton">&times;</span>
        <h2>Добавить новый режим</h2>
        <form id="addPresetForm">
            <div class="form-group">
                <label for="presetName">Название режима:</label>
                <input type="text" id="presetName" name="name" required>
            </div>
            <fieldset>
                <legend>Температура</legend>
                 <div class="input-row">
                    <div class="input-group">
                        <label for="presetTemp">Температура (°C):</label>
                        <input type="number" id="presetTemp" name="temperature" step="0.1" required>
                    </div>
                    <div class="input-group">
                         <label for="presetTempTolerance">Допуск (°C):</label>
                        <input type="number" id="presetTempTolerance" name="tolerance" step="0.1" value="1.0" required>
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <legend>Влажность</legend>
                 <div class="input-row">
                    <div class="input-group">
                        <label for="presetHumidity">Влажность (%):</label>
                        <input type="number" id="presetHumidity" name="humidity" step="1" required>
                     </div>
                     <div class="input-group">
                        <label for="presetHumidityTolerance">Допуск (%):</label>
                        <input type="number" id="presetHumidityTolerance" name="humidity_tolerance" step="0.1" value="5.0" required>
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <legend>Освещение</legend>
                 <div class="input-row">
                     <div class="input-group">
                        <label for="presetLightHours">Часов в день:</label>
                        <input type="number" id="presetLightHours" name="light_hours" step="0.5" required>
                     </div>
                    <div class="input-group">
                        <label for="presetLightStart">Начало (чч:мм):</label>
                        <input type="time" id="presetLightStart" name="light_start" required>
                    </div>
                    <div class="input-group">
                        <label for="presetLightEnd">Конец (чч:мм):</label>
                        <input type="time" id="presetLightEnd" name="light_end" required>
                    </div>
                </div>
            </fieldset>
            <div class="form-actions">
                 <button type="submit" class="btn-save">Сохранить режим</button>
            </div>
        </form>
    </div>
</div>
<!-- Модальное окно для шаринга режима -->
<div class="modal" id="sharePresetModal">
    <div class="modal-content">
        <span class="close-button" id="closeShareModalButton">&times;</span>
        <h2>Поделиться режимом</h2>
        <p>Выберите способ, которым вы хотите поделиться режимом: <span id="shareModeName" class="fw-bold"></span></p>
        <div class="share-options">
            <button class="btn btn-primary mb-2 w-100" id="shareCodeBtn">
                <i class="fas fa-key me-2"></i> Поделиться кодом
            </button>
            <button class="btn btn-info mb-2 w-100" id="shareLinkBtn">
                <i class="fas fa-link me-2"></i> Поделиться ссылкой
            </button>
        </div>
        <div id="shareCodeSection" class="d-none share-result-section">
            <p>Код для импорта режима:</p>
            <div class="input-group">
                <input type="text" id="shareCodeDisplay" class="form-control share-input" readonly>
                <button class="btn btn-outline-secondary copy-btn" type="button" id="copyCodeBtn">
                    📋 Копировать
                </button>
            </div>
            <p class="text-muted small mt-2">Передайте этот код другому пользователю для импорта режима</p>
        </div>
        <div id="shareLinkSection" class="d-none share-result-section">
            <p>Ссылка для импорта режима:</p>
            <div class="input-group">
                <input type="text" id="shareLinkDisplay" class="form-control share-input" readonly>
                <button class="btn btn-outline-secondary copy-btn" type="button" id="copyLinkBtn">
                    📋 Копировать
                </button>
            </div>
            <p class="text-muted small mt-2">Отправьте эту ссылку другому пользователю для прямого импорта режима</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="closeShareModalFooterBtn">Закрыть</button>
        </div>
    </div>
</div>
<style>
.preset-modes-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.preset-mode {
    background: var(--card-bg);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.preset-mode:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}
.preset-mode h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: var(--primary-color);
    font-size: 18px;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 10px;
}
.preset-params {
    margin-bottom: 15px;
}
.preset-param {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}
.param-icon {
    margin-right: 10px;
    font-size: 18px;
}
.param-label {
    font-weight: bold;
    margin-right: 5px;
}
.param-value {
    color: var(--secondary-text);
}
.preset-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 15px;
}
.activate-button {
   flex-grow: 1;
}
.share-button,
.delete-button {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    border-radius: 5px;
    padding: 8px 10px;
    margin: 0;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    min-width: 40px;
    min-height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}
.share-button:hover,
.delete-button:hover {
    background: var(--hover-color);
    transform: translateY(-2px);
    box-shadow: 0 3px 5px rgba(0,0,0,0.2);
}
.delete-button {
    border-color: #dc3545;
    color: #dc3545;
}
.delete-button:hover {
    background: #dc3545;
    color: white;
}
/* Стили для модального окна */
.modal {
    display: none; /* Скрыто по умолчанию */
    position: fixed; 
    z-index: 1000; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0,0,0,0.6); 
}
.modal-content {
    background-color: var(--card-bg);
    margin: 10% auto; 
    padding: 30px;
    border: 1px solid var(--border-color);
    width: 80%; 
    max-width: 600px;
    border-radius: 10px;
    position: relative;
    overflow: hidden; /* Для предотвращения выхода контента за границы */
    max-height: 90vh; /* Максимальная высота */
    overflow-y: auto; /* Добавляем скроллинг если контент не помещается */
}
.close-button {
    color: var(--secondary-text);
    position: absolute;
    top: 10px;
    right: 20px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.close-button:hover,
.close-button:focus {
    color: var(--text-color);
    text-decoration: none;
}
#addPresetForm fieldset {
    border: 1px solid var(--border-color);
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 20px;
}
#addPresetForm legend {
    font-weight: bold;
    padding: 0 10px;
    color: var(--primary-color);
    margin-bottom: 10px;
}
#addPresetForm .input-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
#addPresetForm .form-group {
    width: 100%;
}
#addPresetForm .input-group {
    flex: 1;
    min-width: 150px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}
#addPresetForm label {
    display: block;
    font-size: 14px;
    font-weight: normal;
}
#addPresetForm input[type="text"],
#addPresetForm input[type="number"],
#addPresetForm input[type="time"] {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background: var(--input-bg);
    color: var(--text-color);
    box-sizing: border-box;
}
#addPresetForm .form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px solid var(--border-color);
}
#addPresetForm .btn-save {
    display: block;
    width: auto;
    min-width: 150px;
    margin-left: auto;
    margin-right: 0;
}
@media (max-width: 768px) {
    .preset-modes-container {
        grid-template-columns: 1fr;
    }
}
.preset-header {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 15px;
}
.import-code-container {
    margin-bottom: 1rem;
    background: var(--card-bg);
    border-radius: 10px;
    padding: 20px 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    width: 90%;
    max-width: 800px;
    text-align: center;
}
.import-code-container h4 {
    display: none;
}
.import-code-container .input-group {
    display: flex;
    width: 100%;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}
.import-code-container input {
    flex: 0 1 60%;
    min-width: 200px;
    max-width: 500px;
    font-size: 18px;
    padding: 12px 15px;
    border-radius: 10px;
    color: var(--text-color);
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    margin: 0 auto;
}
#importCodeBtn {
    background-color: #28a745 !important;
    color: white !important;
    border: none;
    border-radius: 10px;
    padding: 12px 20px;
    font-weight: bold;
    transition: all 0.3s ease;
    white-space: nowrap;
    min-width: 180px;
}
#importCodeBtn:hover {
    background-color: #28a745 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}
@media (max-width: 576px) {
    .import-code-container .input-group {
        flex-direction: column;
    }
    .import-code-container input,
    #importCodeBtn {
        width: 100%;
        min-width: auto;
    }
}
.share-options {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: 300px;
    margin: 0 auto 20px;
}
/* Стили для input-group контейнеров */
.input-group {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 15px auto;
    width: 90%;
    max-width: 400px;
    align-items: center;
    justify-content: center;
}
/* Медиа запрос для маленьких экранов */
@media (max-width: 576px) {
    .share-result-section {
        padding: 15px 10px;
    }
    .input-group {
        width: 95%;
    }
    .share-input {
        width: 100%;
        padding: 10px;
        font-size: 13px;
    }
    .copy-btn {
        padding: 10px;
        max-width: 100%;
    }
    .modal-content {
        padding: 20px 15px;
        width: 95%;
        max-width: 95%;
        margin: 5% auto;
    }
    .close-button {
        top: 5px;
        right: 10px;
        font-size: 24px;
    }
}
/* Добавляем стили для секций результатов шаринга */
.share-result-section {
    background-color: var(--card-bg);
    border-radius: 8px;
    padding: 20px;
    border: 1px solid var(--border-color);
    margin: 20px auto;
    text-align: center;
    width: 100%;
    box-sizing: border-box;
}
.share-input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--input-bg);
    color: var(--text-color);
    text-align: center;
    font-size: 15px;
    font-family: monospace;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 15px;
}
.copy-btn {
    width: 100%;
    max-width: 200px;
    padding: 12px 15px;
    border-radius: 6px;
    cursor: pointer;
    background: #007bff;
    border: 1px solid #007bff;
    color: white;
    font-weight: bold;
    margin: 0 auto;
    display: block;
}
.copy-btn:hover {
    background: #0069d9;
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.text-muted {
    color: var(--secondary-text) !important;
}
.small {
    font-size: 85%;
}
.mt-2 {
    margin-top: 0.5rem;
}
.modal-footer {
    padding-top: 15px;
    border-top: 1px solid var(--border-color);
    text-align: right;
}
.d-none {
    display: none !important;
}
.mb-2 {
    margin-bottom: 0.5rem;
}
.mb-4 {
    margin-bottom: 1rem;
}
.w-100 {
    width: 100%;
}
.btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: all 0.3s ease;
    white-space: nowrap;
}
.btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}
.btn-info {
    color: #fff;
    background-color: #17a2b8;
    border-color: #17a2b8;
}
.btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
}
.fw-bold {
    font-weight: bold;
}
#shareCodeSection button:hover,
#shareLinkSection button:hover {
    background: #0069d9;
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
@media (max-width: 576px) {
    .share-options button {
        font-size: 14px;
        padding: 8px;
    }
    #shareModeName {
        word-break: break-word;
        display: inline-block;
    }
    .modal-content h2 {
        font-size: 20px;
    }
    /* Улучшаем отображение на маленьких экранах */
    #shareCodeSection p,
    #shareLinkSection p {
        font-size: 14px;
        margin-bottom: 8px;
    }
}
#shareCodeDisplay,
#shareLinkDisplay {
    font-family: monospace;
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    direction: ltr;
    text-align: left;
    cursor: text;
    background-color: rgba(0, 0, 0, 0.05);
}
.share-options button {
    font-size: 16px;
    padding: 10px;
    transition: all 0.3s ease;
    display: block;
    width: 100%;
    margin-bottom: 10px;
}
.share-options button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
/* Дополнительные стили для мобильных устройств */
@media (max-width: 576px) {
    .modal-footer .btn {
        width: 100%;
        margin-top: 10px;
        font-size: 14px;
        padding: 8px 5px;
    }
    #shareModeName {
        word-break: break-word;
        display: inline-block;
    }
    .share-options {
        max-width: 250px;
    }
}
</style>
<script>
function timeToMinutes(timeStr) {
    if (!timeStr || !timeStr.includes(':')) return 0;
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
function calculateTotalHours(startTime, endTime) {
    const start = timeToMinutes(startTime);
    const end = timeToMinutes(endTime);
    let totalMinutes = end - start;
    if (totalMinutes < 0) {
        totalMinutes += 24 * 60; 
    }
    return totalMinutes / 60; 
}
function togglePresetModes() {
    const content = document.getElementById('presetModesContent');
    const icon = document.getElementById('presetModesIcon');
    content.classList.toggle('active');
    icon.classList.toggle('rotate');
}
async function activatePresetMode(modeData) {
    console.log('Активация режима:', modeData);
    try {
        const response = await fetch('/api/activate-preset-mode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                mode_id: modeData.id
            })
        });
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Ошибка сервера: ${response.status}. ${errorText}`);
        }
        const result = await response.json();
        if (result.success) {
            const farmSettingsContent = document.getElementById('farmSettingsContent');
            const farmSettingsHeader = document.querySelector('.farm-settings-header');
            if (farmSettingsContent && !farmSettingsContent.classList.contains('active') && farmSettingsHeader) {
                farmSettingsHeader.click();
            }
            if (farmSettingsHeader) {
                farmSettingsHeader.scrollIntoView({ behavior: 'smooth' });
            }
            alert(`Режим "${modeData.name}" активирован успешно!`);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(result.message || 'Неизвестная ошибка при активации режима');
        }
    } catch (error) {
        console.error('Ошибка при активации режима:', error);
        alert('Не удалось активировать режим: ' + error.message);
    }
}
async function saveNewPresetMode(formData) {
    try {
        const submitButton = document.querySelector('#addPresetForm button[type="submit"]');
        if (!submitButton) {
            console.error('Не найдена кнопка отправки формы');
            throw new Error('Внутренняя ошибка интерфейса. Пожалуйста, обновите страницу.');
        }
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Сохранение...';
        submitButton.disabled = true;
        const response = await fetch('/api/save-preset-mode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Ошибка сервера:', response.status, response.statusText, errorText);
            throw new Error(`Ошибка сервера: ${response.status}. Проверьте логи сервера.`);
        }
        const result = await response.json();
        if (result.success) {
            const modalElement = document.getElementById('addPresetModal');
            if (modalElement) {
                modalElement.style.display = 'none';
            }
            const form = document.getElementById('addPresetForm');
            if (form) {
                form.reset();
            }
            alert('Режим успешно сохранен!');
            location.reload();
        } else {
            throw new Error(result.message || 'Неизвестная ошибка');
        }
    } catch (error) {
        console.error('Ошибка при сохранении режима:', error);
        alert('Ошибка: ' + error.message);
    } finally {
        const submitButton = document.querySelector('#addPresetForm button[type="submit"]');
        if (submitButton) {
            submitButton.innerHTML = 'Сохранить режим';
            submitButton.disabled = false;
        }
    }
}
function checkUrlForImportCode() {
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get('code');
    if (code) {
        console.log('Обнаружен код импорта в URL:', code);
        if (history.pushState) {
            const newUrl = window.location.pathname;
            window.history.pushState({path: newUrl}, '', newUrl);
        }
        const importInput = document.getElementById('importCodeInput');
        if (importInput) {
            importInput.value = code;
            const presetModesHeader = document.getElementById('presetModesHeader');
            if (presetModesHeader) {
                presetModesHeader.scrollIntoView({ behavior: 'smooth' });
                const presetModesContent = document.getElementById('presetModesContent');
                if (presetModesContent && !presetModesContent.classList.contains('active')) {
                    togglePresetModes();
                }
                setTimeout(() => {
                    const importBtn = document.getElementById('importCodeBtn');
                    if (importBtn) {
                        importBtn.click();
                    }
                }, 500);
            }
        }
    }
}
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, инициализация обработчиков событий');
    initActivateButtons();
    initDeleteButtons();
    initShareButtons();
    setupImportByCode();
    checkUrlForImportCode();
    const presetModesHeader = document.getElementById('presetModesHeader');
    if(presetModesHeader) {
        presetModesHeader.addEventListener('click', togglePresetModes);
    }
    const importCodeBtn = document.getElementById('importCodeBtn');
    if (importCodeBtn) {
        importCodeBtn.textContent = 'Загрузить код';
    }
    const addPresetButton = document.getElementById('addPresetButton');
    const closeModalButton = document.getElementById('closeModalButton');
    const addPresetForm = document.getElementById('addPresetForm');
    const modal = document.getElementById('addPresetModal');
    if(addPresetButton) {
        addPresetButton.addEventListener('click', function() {
            if(modal) modal.style.display = 'block';
        });
    }
    if(closeModalButton) {
        closeModalButton.addEventListener('click', function() {
            if(modal) {
                modal.style.display = 'none';
                if(addPresetForm) addPresetForm.reset();
            }
        });
    }
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            if(addPresetForm) addPresetForm.reset();
        }
    });
    if(addPresetForm) {
        addPresetForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            if (validatePresetFormData(data)) {
                saveNewPresetMode(data);
            }
        });
    }
});
function initActivateButtons() {
    console.log('Инициализация кнопок активации');
    document.querySelectorAll('.activate-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Клик по кнопке активации');
            try {
                const modeData = JSON.parse(this.dataset.mode);
                activatePresetMode(modeData);
            } catch(e) {
                console.error("Ошибка парсинга данных режима:", e);
                alert("Не удалось загрузить данные режима.");
            }
        });
    });
}
function initDeleteButtons() {
    console.log('Инициализация кнопок удаления');
    document.querySelectorAll('.delete-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Клик по кнопке удаления');
            const modeId = this.dataset.id;
            const modeName = this.dataset.name || 'Режим';
            console.log('Нажата кнопка удаления для режима:', modeId, modeName);
            deletePresetMode(modeId, modeName);
        });
    });
}
function initShareButtons() {
    console.log('Инициализация кнопок шаринга');
    const shareModalElement = document.getElementById('sharePresetModal');
    if (!shareModalElement) {
        console.error('Модальное окно шаринга не найдено');
        return;
    }
    const closeShareModalButton = document.getElementById('closeShareModalButton');
    const closeShareModalFooterBtn = document.getElementById('closeShareModalFooterBtn');
    function closeShareModal() {
        shareModalElement.style.display = 'none';
    }
    if (closeShareModalButton) {
        closeShareModalButton.addEventListener('click', closeShareModal);
    }
    if (closeShareModalFooterBtn) {
        closeShareModalFooterBtn.addEventListener('click', closeShareModal);
    }
    window.addEventListener('click', function(event) {
        if (event.target === shareModalElement) {
            closeShareModal();
        }
    });
    document.querySelectorAll('.share-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Клик по кнопке шаринга');
            const modeId = this.dataset.id;
            const modeName = this.dataset.name || 'Режим';
            console.log('Данные кнопки шаринга:', {
                modeId: modeId,
                modeName: modeName,
                button: this
            });
            const shareModeName = document.getElementById('shareModeName');
            if (shareModeName) {
                shareModeName.textContent = modeName;
            }
            const shareCodeSection = document.getElementById('shareCodeSection');
            const shareLinkSection = document.getElementById('shareLinkSection');
            if (shareCodeSection) shareCodeSection.classList.add('d-none');
            if (shareLinkSection) shareLinkSection.classList.add('d-none');
            window.currentShareModeId = modeId;
            shareModalElement.style.display = 'block';
            console.log('Модальное окно шаринга открыто');
        });
    });
    initShareModalButtons();
}
function initShareModalButtons() {
    const shareCodeBtn = document.getElementById('shareCodeBtn');
    const shareLinkBtn = document.getElementById('shareLinkBtn');
    const copyCodeBtn = document.getElementById('copyCodeBtn');
    const copyLinkBtn = document.getElementById('copyLinkBtn');
    function setupInputField(field, value) {
        if (!field) return;
        field.value = value;
        field.addEventListener('focus', function() {
            this.select();
        });
        field.style.resize = 'none';
        field.readOnly = true;
        if (value.length > 40) {
            field.style.fontSize = '13px';
        }
        const container = field.closest('#shareCodeSection') || field.closest('#shareLinkSection');
        if (container) {
            container.classList.remove('d-none');
        }
    }
    if (shareCodeBtn) {
        shareCodeBtn.addEventListener('click', async function() {
            console.log('Клик по кнопке "Поделиться кодом"', { currentModeId: window.currentShareModeId });
            if (!window.currentShareModeId) {
                console.error('ID режима не определен');
                return;
            }
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Получение кода...';
            this.disabled = true;
            try {
                const shareCode = await generateShareCode(window.currentShareModeId);
                console.log('Получен код шаринга:', shareCode);
                if (shareCode) {
                    const shareCodeDisplay = document.getElementById('shareCodeDisplay');
                    const shareCodeSection = document.getElementById('shareCodeSection');
                    const shareLinkSection = document.getElementById('shareLinkSection');
                    setupInputField(shareCodeDisplay, shareCode);
                    if (shareCodeSection) shareCodeSection.classList.remove('d-none');
                    if (shareLinkSection) shareLinkSection.classList.add('d-none');
                    window.currentShareCode = shareCode;
                }
            } catch (error) {
                console.error('Ошибка при получении кода шаринга:', error);
                alert('Не удалось получить код для шаринга: ' + error.message);
            } finally {
                this.innerHTML = '<i class="fas fa-key me-2"></i> Поделиться кодом';
                this.disabled = false;
            }
        });
    }
    if (shareLinkBtn) {
        shareLinkBtn.addEventListener('click', async function() {
            console.log('Клик по кнопке "Поделиться ссылкой"', { currentModeId: window.currentShareModeId });
            if (!window.currentShareModeId) {
                console.error('ID режима не определен');
                return;
            }
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Создание ссылки...';
            this.disabled = true;
            try {
                let shareCode = window.currentShareCode;
                if (!shareCode) {
                    shareCode = await generateShareCode(window.currentShareModeId);
                    console.log('Получен новый код шаринга для ссылки:', shareCode);
                    window.currentShareCode = shareCode;
                }
                if (shareCode) {
                    const shareLinkDisplay = document.getElementById('shareLinkDisplay');
                    const shareCodeSection = document.getElementById('shareCodeSection');
                    const shareLinkSection = document.getElementById('shareLinkSection');
                    const currentUrl = window.location.href;
                    const urlParts = currentUrl.split('?')[0]; 
                    const baseUrl = urlParts; 
                    const shareLink = `${baseUrl}?code=${shareCode}`;
                    console.log('Сгенерирована ссылка:', shareLink);
                    setupInputField(shareLinkDisplay, shareLink);
                    if (shareLinkSection) shareLinkSection.classList.remove('d-none');
                    if (shareCodeSection) shareCodeSection.classList.add('d-none');
                }
            } catch (error) {
                console.error('Ошибка при создании ссылки для шаринга:', error);
                alert('Не удалось создать ссылку для шаринга: ' + error.message);
            } finally {
                this.innerHTML = '<i class="fas fa-link me-2"></i> Поделиться ссылкой';
                this.disabled = false;
            }
        });
    }
    if (copyCodeBtn) {
        copyCodeBtn.addEventListener('click', function() {
            const shareCodeDisplay = document.getElementById('shareCodeDisplay');
            if (shareCodeDisplay) {
                shareCodeDisplay.select();
                document.execCommand('copy');
                const originalText = this.innerHTML;
                this.innerHTML = '✅ Скопировано!';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            }
        });
    }
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', function() {
            const shareLinkDisplay = document.getElementById('shareLinkDisplay');
            if (shareLinkDisplay) {
                shareLinkDisplay.select();
                document.execCommand('copy');
                const originalText = this.innerHTML;
                this.innerHTML = '✅ Скопировано!';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            }
        });
    }
}
function validatePresetFormData(data) {
    const temp = parseFloat(data.temperature);
    if (isNaN(temp) || temp < 20 || temp > 50) {
        alert('Ошибка: Температура должна быть числом от 20 до 50°C.');
        return false;
    }
    const humidity = parseInt(data.humidity, 10);
    if (isNaN(humidity) || humidity < 30 || humidity > 99) {
        alert('Ошибка: Влажность должна быть целым числом от 30 до 99%.');
        return false;
    }
    const requiredHoursInput = parseFloat(data.light_hours);
    const startTimeInput = data.light_start;
    const endTimeInput = data.light_end;
    if (requiredHoursInput && startTimeInput && endTimeInput) {
        const calculatedHours = calculateTotalHours(startTimeInput, endTimeInput);
        if (Math.abs(calculatedHours - requiredHoursInput) > 0.05) { 
            alert(`Ошибка: Заданный временной промежуток (${calculatedHours.toFixed(2)} ч) не соответствует указанному количеству часов (${requiredHoursInput} ч).`);
            return false;
        }
    } else if (!requiredHoursInput && startTimeInput && endTimeInput) {
        const calculatedHours = calculateTotalHours(startTimeInput, endTimeInput);
        data.light_hours = calculatedHours.toFixed(2); 
    } else if (!startTimeInput || !endTimeInput) {
        alert('Пожалуйста, укажите время начала и конца освещения.');
        return false;
    }
    data.temperature = temp;
    data.tolerance = parseFloat(data.tolerance);
    data.humidity = humidity;
    data.humidity_tolerance = parseFloat(data.humidity_tolerance);
    data.light_hours = parseFloat(data.light_hours);
    return true;
}
async function deletePresetMode(modeId, modeName) {
    if (!confirm(`Вы уверены, что хотите удалить режим "${modeName}"?`)) {
        return; 
    }
    console.log("Попытка удалить режим ID:", modeId);
    try {
        const response = await fetch('/api/delete-preset-mode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ mode_id: parseInt(modeId) })
        });
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Ошибка сервера при удалении:', response.status, response.statusText, errorText);
            throw new Error(`Ошибка сервера: ${response.status}. Убедитесь, что файл /api/delete-preset-mode.php существует.`);
        }
        const result = await response.json();
        if (result.success) {
            alert('Режим "' + modeName + '" успешно удален.');
            const modeCard = document.querySelector(`.preset-mode[data-id="${modeId}"]`);
            if (modeCard) {
                modeCard.remove();
            }
        } else {
            throw new Error(result.message || 'Неизвестная ошибка API при удалении');
        }
    } catch (error) {
        console.error('Ошибка при удалении режима:', error);
        alert('Не удалось удалить режим: ' + error.message);
    }
}
async function generateShareCode(modeId) {
    try {
        const response = await fetch('/api/share-preset-mode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ mode_id: parseInt(modeId) })
        });
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Ошибка сервера при генерации кода:', response.status, response.statusText, errorText);
            throw new Error(`Ошибка сервера: ${response.status}. Убедитесь, что файл api/share-preset-mode.php существует.`);
        }
        const result = await response.json();
        if (result.success) {
            return result.share_code;
        } else {
            throw new Error(result.message || 'Неизвестная ошибка API');
        }
    } catch (error) {
        console.error('Ошибка при генерации кода:', error);
        alert('Не удалось создать код для шаринга: ' + error.message);
        return null;
    }
}
function setupImportByCode() {
    const importBtn = document.getElementById('importCodeBtn');
    const importInput = document.getElementById('importCodeInput');
    importBtn.addEventListener('click', async function() {
        const code = importInput.value.trim();
        if (!code) {
            alert('Пожалуйста, введите код режима');
            return;
        }
        if (!/^[A-Z0-9]{8}$/.test(code)) {
            alert('Код должен состоять из 8 символов (буквы и цифры)');
            return;
        }
        importBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        importBtn.disabled = true;
        try {
            const response = await fetch('/api/import-preset-mode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ code: code })
            });
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Ошибка сервера при импорте:', response.status, response.statusText, errorText);
                throw new Error(`Ошибка сервера: ${response.status}. Проверьте API импорта режима.`);
            }
            const result = await response.json();
            if (result.success) {
                alert('Режим успешно импортирован! Страница будет перезагружена для отображения нового режима.');
                importInput.value = '';
                window.location.reload();
            } else {
                alert('Ошибка: ' + (result.message || 'Не удалось импортировать режим'));
            }
        } catch (error) {
            alert('Ошибка: ' + error.message);
            console.error('Ошибка импорта режима:', error);
        } finally {
            importBtn.innerHTML = 'Импортировать';
            importBtn.disabled = false;
        }
    });
    importInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            importBtn.click();
        }
    });
}
</script> 