document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = themeToggle.querySelector('.theme-icon');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    themeToggle.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
    });
    function updateThemeIcon(theme) {
        themeIcon.textContent = theme === 'light' ? '🌙' : '☀️';
    }
    const accordions = document.querySelectorAll('.accordion');
    accordions.forEach(accordion => {
        const header = accordion.querySelector('.accordion-header');
        const content = accordion.querySelector('.accordion-content');
        const icon = accordion.querySelector('.accordion-icon');
        header.addEventListener('click', () => {
            content.classList.toggle('active');
            icon.classList.toggle('active');
        });
    });
    const calendarDays = document.getElementById('calendar-days');
    const currentMonth = document.getElementById('current-month');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    let currentDate = new Date();
    function updateCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        currentMonth.textContent = new Date(year, month).toLocaleString('ru', { month: 'long', year: 'numeric' });
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        calendarDays.innerHTML = '';
        for (let i = 0; i < firstDay.getDay(); i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'calendar-day empty';
            calendarDays.appendChild(emptyCell);
        }
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dayCell = document.createElement('div');
            dayCell.className = 'calendar-day';
            dayCell.textContent = day;
            calendarDays.appendChild(dayCell);
        }
    }
    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        updateCalendar();
    });
    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        updateCalendar();
    });
    updateCalendar();
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Настройки сохранены!');
        });
    });
    const activateButtons = document.querySelectorAll('.activate-button');
    activateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const mode = this.closest('.preset-mode');
            const modeName = mode.querySelector('h3').textContent;
            alert(`Активирован режим: ${modeName}`);
        });
    });
    const eventTypeSelect = document.getElementById('event-type');
    const eventDateInput = document.getElementById('event-date');
    const eventItems = document.querySelectorAll('.event-item');
    function filterEvents() {
        const selectedType = eventTypeSelect.value;
        const selectedDate = eventDateInput.value;
        eventItems.forEach(item => {
            const matchesType = selectedType === 'all' || item.classList.contains(selectedType);
            const eventDate = item.querySelector('.event-time').textContent.split(' ')[0];
            const matchesDate = !selectedDate || eventDate === selectedDate;
            item.style.display = matchesType && matchesDate ? 'block' : 'none';
        });
    }
    eventTypeSelect.addEventListener('change', filterEvents);
    eventDateInput.addEventListener('change', filterEvents);
});