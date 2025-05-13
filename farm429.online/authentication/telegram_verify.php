<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/telegram.php';
require_once '../config/headers.php';
// Проверяем наличие временных данных
if (!isset($_SESSION['temp_user'])) {
    header("Location: register.php");
    exit;
}
$temp_user = $_SESSION['temp_user'];
$bot_username = TELEGRAM_BOT_USERNAME;
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Верификация Telegram - ФитоДомик</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="apple-touch-icon" sizes="180x180" href="../icon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../icon/favicon-16x16.png">
    <link rel="manifest" href="../icon/site.webmanifest">
    <link rel="shortcut icon" href="../icon/favicon.ico">
</head>
<body>
    <div class="auth-container">
        <button id="theme-toggle" class="theme-toggle auth-theme-toggle">
            <span class="theme-icon">🌙</span>
        </button>
        <div class="auth-form">
            <h2>Верификация через Telegram</h2>
            <div class="verification-info">
                <h4>Инструкция по верификации:</h4>
                <ol>
                    <li>Перейдите в Telegram бот: <a href="https://t.me/FitoDomik_bot" target="_blank">@<?php echo htmlspecialchars($bot_username); ?></a></li>
                    <li>Отправьте команду /start</li>
                    <li>Введите ваш никнейм: <strong><?php echo htmlspecialchars($temp_user['username']); ?></strong></li>
                    <li>Дождитесь подтверждения верификации</li>
                </ol>
            </div>
            <div class="text-center">
                <a href="https://t.me/FitoDomik_bot" class="auth-button" target="_blank">
                    Перейти в Telegram бот
                </a>
            </div>
            <div class="mt-3 text-center">
                <p>После верификации вы будете перенаправлены на главную страницу.</p>
                <div id="verification-status"></div>
            </div>
            <div class="profile-actions">
                <a href="register.php" class="auth-button secondary">Вернуться к регистрации</a>
                <a href="../index.php" class="auth-button secondary return-profile">Вернуться на главную</a>
            </div>
        </div>
    </div>
    <script>
        // Показывать имя пользователя и время последней проверки для отладки
        let checkCount = 0;
        const statusDiv = document.getElementById('verification-status');
        function checkVerificationStatus() {
            checkCount++;
            statusDiv.innerHTML = '<div>Проверка #' + checkCount + ' для пользователя <?php echo htmlspecialchars($temp_user['username']); ?> (' + new Date().toLocaleTimeString() + ')</div>';
            fetch('../api/check_verification.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Ответ от сервера:', data);
                    if (data.verified) {
                        statusDiv.innerHTML = 
                            '<div class="success-message">Верификация успешно завершена!</div>';
                        setTimeout(() => {
                            window.location.href = '../index.php';
                        }, 2000);
                    } else {
                        statusDiv.innerHTML += '<div>Ожидание верификации... (' + data.message + ')</div>';
                        setTimeout(checkVerificationStatus, 5000);
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    statusDiv.innerHTML += '<div>Ошибка при проверке верификации</div>';
                    setTimeout(checkVerificationStatus, 5000);
                });
        }
        checkVerificationStatus();
    </script>
    <script src="js/theme.js"></script>
</body>
</html> 