document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.querySelector('.theme-toggle');
    if (!themeToggle) {
        console.error('Элемент переключения темы не найден');
        return;
    }
    const themeIcon = themeToggle.querySelector('.theme-icon');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    themeToggle.removeAttribute('onclick');
    if(!themeToggle.getAttribute('data-listener')) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
            updateTimeInputsTheme(newTheme);
        });
        themeToggle.setAttribute('data-listener', 'true');
    }
    function updateThemeIcon(theme) {
        if (themeIcon) {
            themeIcon.innerHTML = theme === 'light' ? '🌙' : '☀️';
        }
    }
    function updateTimeInputsTheme(theme) {
        const timeInputs = document.querySelectorAll('input[type="time"]');
        if (theme === 'dark') {
            timeInputs.forEach(input => {
                input.style.backgroundColor = '#333';
                input.style.color = '#fff';
                input.style.borderColor = '#555';
            });
        } else {
            timeInputs.forEach(input => {
                input.style.backgroundColor = '';
                input.style.color = '';
                input.style.borderColor = '';
            });
        }
    }
    updateTimeInputsTheme(savedTheme);
}); 