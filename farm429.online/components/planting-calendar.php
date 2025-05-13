<?php
$isGuest = !isset($_SESSION['user_id']);
?>
<div class="planting-section">
    <div class="section-header green-header" onclick="toggleCalendarContent()">
        <span>Календарь посадки 🌱</span>
        <span class="accordion-icon">▼</span>
    </div>
    <div class="accordion-content" itemscope itemtype="https://schema.org/Event">
        <meta itemprop="name" content="Календарь посадок и ухода за растениями">
        <meta itemprop="description" content="Интерактивный календарь посадок и ухода за растениями в умной ферме">
        <meta itemprop="startDate" content="<?php echo date('Y-m-d'); ?>">
        <?php if ($isGuest): ?>
        <div class="guest-notice">
            <p>Для доступа к полному функционалу календаря необходимо <a href="authentication/login.php">авторизоваться</a>.</p>
        </div>
        <?php endif; ?>
        <div class="calendar-container">
            <div class="calendar-controls">
                <button id="prev-month" class="btn btn-sm btn-outline-secondary">←</button>
                <h3 id="current-month">Январь 2024</h3>
                <button id="next-month" class="btn btn-sm btn-outline-secondary">→</button>
            </div>
            <div class="calendar-grid">
                <div class="calendar-header">
                    <div>Пн</div>
                    <div>Вт</div>
                    <div>Ср</div>
                    <div>Чт</div>
                    <div>Пт</div>
                    <div>Сб</div>
                    <div>Вс</div>
                </div>
                <div class="calendar-days" id="calendar-days">
                    <!-- Дни календаря будут добавлены через JavaScript -->
                </div>
            </div>
            <div class="planting-events" id="planting-events">
                <div class="events-header">
                    <h3>События на <span id="selected-date">выбранную дату</span></h3>
                    <div class="action-buttons">
                        <button id="add-event-btn" class="action-button event-button" <?php echo $isGuest ? 'disabled' : ''; ?>>+ Событие</button>
                        <button id="add-reminder-btn" class="action-button reminder-button" <?php echo $isGuest ? 'disabled' : ''; ?>>+ Напоминание</button>
                    </div>
                </div>
                <div id="events-list">
                    <!-- События на выбранную дату будут здесь -->
                    <div class="no-events">Нет событий на выбранную дату</div>
                </div>
            </div>
            <div class="planting-form" id="event-form" style="display: none;">
                <h3 id="form-title">Добавить событие</h3>
                <form id="add-planting-form">
                    <div class="form-group">
                        <label for="event-type">Тип события</label>
                        <select id="event-type" name="event-type" required>
                            <option value="planting">Посадка</option>
                            <option value="sprouting">Всходы</option>
                            <option value="watering">Полив</option>
                            <option value="fertilizing">Удобрение</option>
                            <option value="harvesting">Сбор урожая</option>
                            <option value="reminder">Напоминание</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="plant-name">Название растения</label>
                        <input type="text" id="plant-name" name="plant-name" required>
                    </div>
                    <div class="form-group">
                        <label for="event-date">Дата события</label>
                        <input type="date" id="event-date" name="event-date" required>
                    </div>
                    <div class="form-group time-group">
                        <label for="event-time">Время события</label>
                        <input type="time" id="event-time" name="event-time">
                    </div>
                    <div class="form-group">
                        <label for="event-notes">Заметки</label>
                        <textarea id="event-notes" name="event-notes" rows="3" placeholder="Дополнительная информация о событии"></textarea>
                    </div>
                    <div class="form-group reminder-details" id="reminder-details" style="display: none;">
                        <label for="reminder-date">Дата напоминания</label>
                        <input type="date" id="reminder-date" name="reminder-date">
                        <label for="reminder-time">Время напоминания</label>
                        <input type="time" id="reminder-time" name="reminder-time">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-button">Добавить</button>
                        <button type="button" id="cancel-form-btn" class="cancel-button">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
        <meta itemprop="dateModified" content="<?php echo date('c'); ?>">
    </div>
</div>
<!-- Модальное окно для просмотра/редактирования события -->
<div class="modal" id="event-modal">
    <div class="modal-content">
        <span class="close-button" id="close-event-modal">&times;</span>
        <h2 id="event-modal-title">Детали события</h2>
        <div id="event-modal-content">
            <!-- Содержимое будет заполнено через JavaScript -->
        </div>
        <div class="modal-footer">
            <button id="edit-event-btn" class="btn btn-primary">Редактировать</button>
            <button id="delete-event-btn" class="btn btn-danger">Удалить</button>
            <button id="close-event-btn" class="btn btn-secondary">Закрыть</button>
        </div>
    </div>
</div>
<style>
/* Стили для заголовка раздела */
.planting-section {
    margin-top: 20px;
    margin-bottom: 20px;
}
.section-header {
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 0;
    background-color: #4CAF50 !important;
    color: white !important;
    font-size: 18px;
    font-weight: 500;
}
.green-header {
    background-color: #4CAF50 !important;
    color: white !important;
}
[data-theme="dark"] .section-header, 
[data-theme="dark"] .green-header {
    background-color: #66BB6A !important;
}
.calendar-container {
    background-color: var(--card-bg);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    max-width: 1000px;
    margin: 0 auto;
}
.calendar-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.calendar-controls button {
    background: var(--button-bg);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    padding: 5px 10px;
    cursor: pointer;
    border-radius: 4px;
}
.calendar-controls h3 {
    margin: 0;
    font-size: 20px;
    color: var(--primary-color);
}
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    margin-bottom: 30px;
}
.calendar-header {
    display: contents;
}
.calendar-header div {
    text-align: center;
    font-weight: bold;
    padding: 10px;
    color: var(--primary-color);
}
.calendar-days {
    display: contents;
}
.calendar-day {
    height: 80px;
    background-color: var(--input-bg);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 5px;
    position: relative;
    cursor: pointer;
    overflow: hidden;
    color: var(--text-color);
}
.calendar-day:hover {
    background-color: var(--hover-color);
}
.day-number {
    font-weight: bold;
    font-size: 14px;
    position: absolute;
    top: 5px;
    right: 5px;
    color: var(--text-color);
}
.other-month {
    opacity: 0.5;
}
.today {
    background-color: rgba(var(--primary-rgb), 0.1);
    border: 2px solid var(--primary-color);
}
.selected-day {
    background-color: rgba(var(--primary-rgb), 0.2);
    border: 2px solid var(--primary-color);
}
.has-events {
    position: relative;
}
.has-events:after {
    content: "";
    position: absolute;
    bottom: 5px;
    left: 50%;
    transform: translateX(-50%);
    width: 6px;
    height: 6px;
    background-color: var(--primary-color);
    border-radius: 50%;
}
/* Кнопки действий */
.events-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
}
.events-header h3 {
    margin: 0;
    font-size: 18px;
    color: var(--primary-color);
}
.action-buttons {
    display: flex;
    gap: 10px;
}
.action-button {
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    color: white;
    transition: all 0.2s ease;
}
.event-button {
    background-color: #4CAF50;
}
.event-button:hover {
    background-color: #3e8e41;
    transform: translateY(-2px);
}
.reminder-button {
    background-color: #F44336;
}
.reminder-button:hover {
    background-color: #d32f2f;
    transform: translateY(-2px);
}
.cancel-button {
    background-color: #6c757d;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}
/* Стили для полей формы */
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    color: var(--text-color);
    font-weight: 500;
}
/* Темная тема для полей ввода */
.form-group input[type="text"],
.form-group input[type="date"],
.form-group input[type="time"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background-color: var(--input-bg);
    color: var(--text-color);
    transition: border-color 0.3s ease;
    box-sizing: border-box;
    height: 38px;
}
/* Отдельный стиль для textarea */
.form-group textarea {
    height: auto;
    min-height: 80px;
}
.form-group input[type="text"]:focus,
.form-group input[type="date"]:focus,
.form-group input[type="time"]:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    outline: none;
}
/* Стили для select в темной теме */
.form-group select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 1em;
    padding-right: 30px;
    background-color: var(--input-bg);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}
.dark-theme .form-group select {
    background-color: #2a2a2a;
    color: #e0e0e0;
    border-color: #444;
}
.dark-theme .form-group select option {
    background-color: #2a2a2a;
    color: #e0e0e0;
}
/* Форма добавления событий */
.planting-form {
    background-color: var(--input-bg);
    border-radius: 8px;
    padding: 20px;
    border: 1px solid var(--border-color);
    margin-bottom: 20px;
    overflow: hidden; /* Предотвращает выход дочерних элементов за границы */
}
.planting-form h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: var(--primary-color);
    font-size: 18px;
}
.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
    gap: 10px;
}
.save-button {
    background-color: var(--primary-color);
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}
/* Модальное окно */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}
/* Темная тема для модального окна */
.modal-content {
    background-color: var(--card-bg);
    color: var(--text-color);
    margin: 10% auto;
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
    position: relative;
}
.close-button {
    color: var(--secondary-text);
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.modal-content h2,
.modal-content p strong {
    color: var(--primary-color);
}
.modal-footer {
    padding-top: 15px;
    text-align: right;
    border-top: 1px solid var(--border-color);
    margin-top: 15px;
}
.modal-footer button {
    margin-left: 10px;
}
/* Стили для разделителей */
.planting-events {
    margin-bottom: 30px;
    padding: 15px;
    background-color: var(--input-bg);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}
.event-item {
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    margin-bottom: 10px;
    background-color: var(--card-bg);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    color: var(--text-color);
}
.event-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.event-item h4 {
    margin-top: 0;
    margin-bottom: 5px;
    color: var(--text-color);
    font-size: 16px;
}
.event-item p {
    margin: 5px 0;
    color: var(--secondary-text);
    font-size: 14px;
}
.no-events {
    padding: 15px;
    text-align: center;
    color: var(--secondary-text);
    background-color: var(--card-bg);
    border-radius: 4px;
    border: 1px dashed var(--border-color);
}
/* Стили для типов событий */
.event-type-planting { border-left: 5px solid #4CAF50; }
.event-type-sprouting { border-left: 5px solid #2196F3; }
.event-type-watering { border-left: 5px solid #00BCD4; }
.event-type-fertilizing { border-left: 5px solid #FF9800; }
.event-type-harvesting { border-left: 5px solid #9C27B0; }
.event-type-reminder { border-left: 5px solid #F44336; }
.event-type-other { border-left: 5px solid #607D8B; }
/* Маркеры событий в календаре */
.day-event-marker {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 3px;
}
.marker-planting { background-color: #4CAF50; }
.marker-sprouting { background-color: #2196F3; }
.marker-watering { background-color: #00BCD4; }
.marker-fertilizing { background-color: #FF9800; }
.marker-harvesting { background-color: #9C27B0; }
.marker-reminder { background-color: #F44336; }
.marker-other { background-color: #607D8B; }
.event-markers {
    position: absolute;
    bottom: 5px;
    left: 5px;
    display: flex;
}
/* Дополнительные классы утилит */
.d-none {
    display: none !important;
}
@media (max-width: 768px) {
    .calendar-day {
        height: 60px;
    }
    .events-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .action-buttons {
        width: 100%;
    }
    .action-button {
        flex: 1;
        text-align: center;
    }
    .form-actions {
        flex-direction: column;
        gap: 10px;
    }
    .save-button, .cancel-button, .export-button {
        width: 100%;
    }
}
.dark-theme .form-group input[type="text"],
.dark-theme .form-group input[type="date"],
.dark-theme .form-group input[type="time"],
.dark-theme .form-group textarea {
    background-color: #2a2a2a;
    color: #e0e0e0;
    border-color: #444;
}
/* Дополнительные стили для select и option в разных браузерах */
.form-group select,
.form-group option {
    color: var(--text-color) !important;
    background-color: var(--input-bg) !important;
}
/* Специальные стили для веб-кит браузеров (Chrome, Safari) */
.form-group select::-webkit-scrollbar {
    width: 8px;
}
.form-group select::-webkit-scrollbar-track {
    background: var(--border-color);
}
.form-group select::-webkit-scrollbar-thumb {
    background-color: var(--primary-color);
    border-radius: 4px;
}
/* Стили для Firefox */
@-moz-document url-prefix() {
    .form-group select {
        background-color: var(--input-bg) !important;
        color: var(--text-color) !important;
    }
    .form-group select option {
        background-color: var(--input-bg) !important;
        color: var(--text-color) !important;
    }
}
/* Исправляем стили для выпадающего списка на всех устройствах */
select option {
    padding: 10px;
    line-height: 1.5;
}
/* Специальные правила для темной темы */
[data-theme="dark"] .form-group select,
[data-theme="dark"] .form-group select option {
    background-color: #2a2a2a !important;
    color: #e0e0e0 !important;
    border-color: #444 !important;
}
/* Стиль для полей даты и времени в темной теме */
[data-theme="dark"] .form-group input[type="date"],
[data-theme="dark"] .form-group input[type="time"] {
    background-color: #2a2a2a;
    color: #e0e0e0;
    border-color: #444;
    color-scheme: dark; /* Попытка стилизовать нативный пикер */
}
/* Попытка стилизовать иконку календаря (может не сработать во всех браузерах) */
[data-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
/* Попытка стилизовать иконку часов (еще менее вероятно, что сработает) */
[data-theme="dark"] input[type="time"]::-webkit-calendar-picker-indicator { /* Часто используется тот же индикатор */
    filter: invert(1);
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
button[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}
.accordion-content {
    display: none;
    background-color: var(--card-bg);
    border-radius: 0 0 8px 8px;
    overflow: hidden;
    padding-top: 20px; /* Добавляем отступ сверху */
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.accordion-content.active {
    display: block;
}
.accordion-icon {
    transition: transform 0.3s ease;
}
.accordion-icon.rotate {
    transform: rotate(180deg);
}
</style>
<!-- Скрипты календаря посадки -->
<script>
const isGuestUser = <?php echo $isGuest ? 'true' : 'false'; ?>;
function toggleCalendarContent() {
    const content = document.querySelector('.planting-section .accordion-content');
    const icon = document.querySelector('.planting-section .accordion-icon');
    content.classList.toggle('active');
    icon.classList.toggle('rotate');
}
document.addEventListener('DOMContentLoaded', function() {
    const content = document.querySelector('.planting-section .accordion-content');
    const icon = document.querySelector('.planting-section .accordion-icon');
    if (content && icon) {
    }
    let currentDate = new Date(); 
    let selectedDate = new Date(); 
    let eventsDB = []; 
    const months = [
        'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
        'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
    ];
    const eventTypes = {
        planting: { name: 'Посадка', color: '#4CAF50' },
        sprouting: { name: 'Всходы', color: '#2196F3' },
        watering: { name: 'Полив', color: '#00BCD4' },
        fertilizing: { name: 'Удобрение', color: '#FF9800' },
        harvesting: { name: 'Сбор урожая', color: '#9C27B0' },
        reminder: { name: 'Напоминание', color: '#F44336' },
        other: { name: 'Другое', color: '#607D8B' }
    };
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const currentMonthElement = document.getElementById('current-month');
    const calendarDaysElement = document.getElementById('calendar-days');
    const selectedDateElement = document.getElementById('selected-date');
    const eventsListElement = document.getElementById('events-list');
    const addEventBtn = document.getElementById('add-event-btn');
    const addReminderBtn = document.getElementById('add-reminder-btn');
    const eventForm = document.getElementById('event-form');
    const addPlantingForm = document.getElementById('add-planting-form');
    const formTitle = document.getElementById('form-title');
    const cancelFormBtn = document.getElementById('cancel-form-btn');
    const eventTypeSelect = document.getElementById('event-type');
    const reminderDetails = document.getElementById('reminder-details');
    const eventDateInput = document.getElementById('event-date');
    const eventTimeInput = document.getElementById('event-time');
    const reminderDateInput = document.getElementById('reminder-date');
    const reminderTimeInput = document.getElementById('reminder-time');
    const eventModal = document.getElementById('event-modal');
    const closeEventModalBtn = document.getElementById('close-event-modal');
    const closeEventBtn = document.getElementById('close-event-btn');
    const deleteEventBtn = document.getElementById('delete-event-btn');
    const editEventBtn = document.getElementById('edit-event-btn');
    const eventModalContent = document.getElementById('event-modal-content');
    const plantingSection = document.querySelector('.planting-section');
    const sectionHeader = plantingSection.querySelector('.section-header');
    const accordionContent = plantingSection.querySelector('.accordion-content');
    const accordionIcon = sectionHeader.querySelector('.accordion-icon');
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    function formatTime(date) {
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    }
    function formatDateRus(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    }
    function formatDateTimeRus(date) {
        return `${formatDateRus(date)} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    }
    function loadEvents() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth() + 1;
        fetch(`api/load-planting-events.php?year=${year}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    eventsDB = data.events;
                    renderCalendar();
                    renderEventsList(selectedDate);
                } else {
                    console.error('Ошибка при загрузке событий:', data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка при запросе событий:', error);
            });
    }
    function saveEvent(formData) {
        fetch('api/save-planting-event.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            } else {
                return response.text().then(text => {
                     throw new Error("Неверный формат ответа от сервера: " + text);
                });
            }
        })
        .then(data => {
            if (data.success) {
                alert('Событие успешно сохранено!');
                loadEvents();
                eventForm.style.display = 'none';
            } else {
                alert('Ошибка сохранения: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка при сохранении:', error);
            alert('Произошла ошибка при сохранении события. Подробности в консоли.');
        });
    }
    function deleteEvent(eventId) {
        if (isGuestUser) {
            alert('Для удаления событий необходимо авторизоваться');
            return;
        }
        if (!confirm('Вы уверены, что хотите удалить это событие?')) return;
        fetch('api/delete-planting-event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId })
        })
        .then(response => response.json()) 
        .then(data => {
            if (data.success) {
                alert('Событие успешно удалено!');
                loadEvents();
                closeModal();
            } else {
                alert('Ошибка удаления: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка при удалении:', error);
            alert('Произошла ошибка при удалении события.');
        });
    }
    function renderCalendar() {
        calendarDaysElement.innerHTML = '';
        currentMonthElement.textContent = `${months[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        let firstDayOfWeek = firstDay.getDay();
        firstDayOfWeek = firstDayOfWeek === 0 ? 7 : firstDayOfWeek;
        const prevMonthLastDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0).getDate();
        for (let i = firstDayOfWeek - 1; i > 0; i--) {
            calendarDaysElement.appendChild(createDayElement(prevMonthLastDay - i + 1, true));
        }
        for (let i = 1; i <= lastDay.getDate(); i++) {
            const dayElement = createDayElement(i, false);
            const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), i);
            const dateStr = formatDate(dayDate);
            const today = new Date();
            if (formatDate(dayDate) === formatDate(today)) {
                dayElement.classList.add('today');
            }
            if (formatDate(dayDate) === formatDate(selectedDate)) {
                dayElement.classList.add('selected-day');
            }
            const dayEvents = eventsDB.filter(event => event.event_date === dateStr);
            if (dayEvents.length > 0) {
                const eventMarkersDiv = document.createElement('div');
                eventMarkersDiv.className = 'event-markers';
                const uniqueEventTypes = [...new Set(dayEvents.map(event => event.type))];
                uniqueEventTypes.forEach(type => {
                    const marker = document.createElement('span');
                    marker.className = `day-event-marker marker-${type}`;
                    eventMarkersDiv.appendChild(marker);
                });
                dayElement.appendChild(eventMarkersDiv);
            }
            dayElement.addEventListener('click', function() {
                document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected-day'));
                this.classList.add('selected-day');
                selectedDate = dayDate;
                eventDateInput.value = formatDate(selectedDate);
                renderEventsList(selectedDate);
            });
            calendarDaysElement.appendChild(dayElement);
        }
        const totalCells = 42;
        const cellsRendered = (firstDayOfWeek - 1) + lastDay.getDate();
        const cellsToAdd = totalCells - cellsRendered;
        for (let i = 1; i <= cellsToAdd; i++) {
            calendarDaysElement.appendChild(createDayElement(i, true));
        }
    }
    function createDayElement(day, isOtherMonth) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        if (isOtherMonth) dayElement.classList.add('other-month');
        const dayNumber = document.createElement('div');
        dayNumber.className = 'day-number';
        dayNumber.textContent = day;
        dayElement.appendChild(dayNumber);
        return dayElement;
    }
    function renderEventsList(date) {
        selectedDateElement.textContent = formatDateRus(date);
        eventsListElement.innerHTML = '';
        const dateStr = formatDate(date);
        const dayEvents = eventsDB.filter(event => event.event_date === dateStr);
        if (dayEvents.length === 0) {
            const noEventsElement = document.createElement('div');
            noEventsElement.className = 'no-events';
            noEventsElement.textContent = 'Нет событий на выбранную дату';
            eventsListElement.appendChild(noEventsElement);
            return;
        }
        dayEvents.sort((a, b) => (a.event_time || '23:59').localeCompare(b.event_time || '23:59'));
        dayEvents.forEach(event => {
            const eventElement = document.createElement('div');
            eventElement.className = `event-item event-type-${event.type}`;
            eventElement.dataset.eventId = event.id;
            const eventTitle = document.createElement('h4');
            eventTitle.textContent = event.plant_name;
            eventElement.appendChild(eventTitle);
            const eventTypeElement = document.createElement('p');
            eventTypeElement.innerHTML = `<strong>Тип:</strong> ${eventTypes[event.type]?.name || event.type}`;
            eventElement.appendChild(eventTypeElement);
            if (event.event_time) {
                const eventTimeElement = document.createElement('p');
                eventTimeElement.innerHTML = `<strong>Время:</strong> ${event.event_time}`;
                eventElement.appendChild(eventTimeElement);
            }
            if (event.notes) {
                const notesElement = document.createElement('p');
                notesElement.innerHTML = `<strong>Заметки:</strong> ${event.notes}`;
                eventElement.appendChild(notesElement);
            }
            if (event.reminder) {
                const reminderElement = document.createElement('p');
                const reminderDate = event.reminder.date ? formatDateRus(new Date(event.reminder.date.split('-').join(','))) : 'N/A';
                const reminderTime = event.reminder.time || '';
                const reminderStatus = event.reminder.is_shown ? ' (показано)' : '';
                reminderElement.innerHTML = `<strong>Напоминание:</strong> ${reminderDate} ${reminderTime}${reminderStatus}`;
                eventElement.appendChild(reminderElement);
            }
            eventElement.addEventListener('click', () => showEventDetails(event.id));
            eventsListElement.appendChild(eventElement);
        });
    }
    function showEventDetails(eventId) {
        const event = eventsDB.find(e => e.id === eventId);
        if (!event) return;
        document.getElementById('event-modal-title').textContent = `Детали события: ${event.plant_name}`;
        let reminderHtml = '';
        if (event.reminder) {
             const reminderDate = event.reminder.date ? formatDateRus(new Date(event.reminder.date.split('-').join(','))) : 'N/A';
             const reminderTime = event.reminder.time || '';
             const reminderStatus = event.reminder.is_shown ? ' (показано)' : '';
             reminderHtml = `<p><strong>Напоминание:</strong> ${reminderDate} ${reminderTime}${reminderStatus}</p>`;
        }
        eventModalContent.innerHTML = `
            <div class="event-details">
                <p><strong>Растение:</strong> ${event.plant_name}</p>
                <p><strong>Тип события:</strong> ${eventTypes[event.type]?.name || event.type}</p>
                <p><strong>Дата:</strong> ${event.event_date ? formatDateRus(new Date(event.event_date.split('-').join(','))) : 'N/A'}</p>
                ${event.event_time ? `<p><strong>Время:</strong> ${event.event_time}</p>` : ''}
                ${event.notes ? `<p><strong>Заметки:</strong> ${event.notes}</p>` : ''}
                ${reminderHtml}
            </div>
        `;
        deleteEventBtn.dataset.eventId = eventId;
        editEventBtn.dataset.eventId = eventId;
        eventModal.style.display = 'block';
    }
    function closeModal() {
        eventModal.style.display = 'none';
    }
    function checkScheduledReminders() {
    }
    function initCalendar() {
        loadEvents();
        const today = new Date();
        eventDateInput.value = formatDate(today);
        checkScheduledReminders();
    }
    if(prevMonthBtn && nextMonthBtn) {
        prevMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            loadEvents();
        });
        nextMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            loadEvents();
        });
    }
    if (addEventBtn) {
        addEventBtn.addEventListener('click', () => {
            if (isGuestUser) {
                alert('Для добавления событий необходимо авторизоваться');
                return;
            }
            eventForm.style.display = 'block';
            formTitle.textContent = 'Добавить событие';
            addPlantingForm.reset();
            eventTypeSelect.value = 'planting';
            const now = new Date();
            eventDateInput.value = formatDate(now);
            eventTimeInput.value = formatTime(now);
            reminderDetails.style.display = 'none';
            eventForm.scrollIntoView({ behavior: 'smooth' });
        });
    }
    if (addReminderBtn) {
        addReminderBtn.addEventListener('click', () => {
            if (isGuestUser) {
                alert('Для добавления напоминаний необходимо авторизоваться');
                return;
            }
            eventForm.style.display = 'block';
            formTitle.textContent = 'Добавить напоминание';
            addPlantingForm.reset();
            eventTypeSelect.value = 'reminder';
            const now = new Date();
            eventDateInput.value = formatDate(now);
            eventTimeInput.value = formatTime(now);
            reminderDetails.style.display = 'block';
            reminderDateInput.value = formatDate(now);
            reminderTimeInput.value = formatTime(now);
            eventForm.scrollIntoView({ behavior: 'smooth' });
        });
    }
    if (eventTypeSelect) {
        eventTypeSelect.addEventListener('change', function() {
            reminderDetails.style.display = (this.value === 'reminder') ? 'block' : 'none';
        });
    }
    if (cancelFormBtn) {
        cancelFormBtn.addEventListener('click', () => {
            eventForm.style.display = 'none';
        });
    }
    if (addPlantingForm) {
        addPlantingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (isGuestUser) {
                alert('Для сохранения событий необходимо авторизоваться');
                return;
            }
            const formData = new FormData(this);
            const now = new Date();
            if (!formData.get('event-date')) formData.set('event-date', formatDate(now));
            if (!formData.get('event-time')) { 
                const currentEventTime = eventTimeInput.value;
                if (!currentEventTime) {
                    formData.set('event-time', formatTime(now));
                } else {
                    formData.set('event-time', currentEventTime); 
                }
            }
            if (formData.get('event-type') === 'reminder') {
                if (!formData.get('reminder-date')) formData.set('reminder-date', formatDate(now));
                const currentReminderTime = reminderTimeInput.value;
                if (!currentReminderTime) {
                    formData.set('reminder-time', formatTime(now));
                } else {
                    formData.set('reminder-time', currentReminderTime); 
                }
            }
            saveEvent(formData);
        });
    }
    if (closeEventModalBtn) closeEventModalBtn.addEventListener('click', closeModal);
    if (closeEventBtn) closeEventBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (event) => {
        if (event.target === eventModal) closeModal();
    });
    if (deleteEventBtn) {
        deleteEventBtn.addEventListener('click', function() {
            const eventId = parseInt(this.dataset.eventId);
            if (eventId) deleteEvent(eventId);
        });
    }
    if (editEventBtn) {
        editEventBtn.addEventListener('click', function() {
            if (isGuestUser) {
                alert('Для редактирования событий необходимо авторизоваться');
                return;
            }
            const eventId = parseInt(this.dataset.eventId);
            const event = eventsDB.find(e => e.id === eventId);
            if (event) {
                formTitle.textContent = 'Редактировать событие';
                addPlantingForm.reset(); 
                let eventIdInput = addPlantingForm.querySelector('input[name="event_id"]');
                if (!eventIdInput) {
                    eventIdInput = document.createElement('input');
                    eventIdInput.type = 'hidden';
                    eventIdInput.name = 'event_id';
                    addPlantingForm.appendChild(eventIdInput);
                }
                eventIdInput.value = event.id;
                eventTypeSelect.value = event.type;
                document.getElementById('plant-name').value = event.plant_name || '';
                eventDateInput.value = event.event_date || '';
                eventTimeInput.value = event.event_time || '';
                document.getElementById('event-notes').value = event.notes || '';
                if (event.type === 'reminder' && event.reminder) {
                    reminderDetails.style.display = 'block';
                    reminderDateInput.value = event.reminder.date || '';
                    reminderTimeInput.value = event.reminder.time || '';
                } else {
                    reminderDetails.style.display = 'none';
                }
                closeModal();
                eventForm.style.display = 'block';
                eventForm.scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Не удалось найти данные для редактирования.');
            }
        });
    }
    function openModal(event) {
        eventModalContent.innerHTML = generateEventHTML(event);
        eventModal.style.display = 'block';
        editEventBtn.dataset.eventId = event.id;
        deleteEventBtn.dataset.eventId = event.id;
        editEventBtn.style.display = isGuestUser ? 'none' : 'inline-block';
        deleteEventBtn.style.display = isGuestUser ? 'none' : 'inline-block';
    }
    initCalendar();
});
</script> 