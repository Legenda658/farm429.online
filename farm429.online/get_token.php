<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'security/security_bootstrap.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: authentication/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Получение токена - ФитоДомик</title>
    <!-- Подключаем стили и иконки через security middleware -->
    <link rel="stylesheet" href="security/css.php?file=styles.css">
    <link rel="apple-touch-icon" sizes="180x180" href="security/image.php?file=icon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="security/image.php?file=icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="security/image.php?file=icon/favicon-16x16.png">
    <link rel="manifest" href="security/manifest.php?file=site.webmanifest">
    <link rel="mask-icon" href="security/image.php?file=icon/safari-pinned-tab.svg" color="#2E7D32">
    <meta name="msapplication-TileColor" content="#2E7D32">
    <meta name="theme-color" content="#2E7D32">
    <!-- Добавляем структурированные данные Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Получение API токена - ФитоДомик",
        "description": "Страница для получения и копирования API токена для доступа к системе управления умной фермой ФитоДомик",
        "publisher": {
            "@type": "Organization",
            "name": "ФитоДомик",
            "logo": {
                "@type": "ImageObject",
                "url": "https://farm429.online/security/image.php?file=icon/apple-touch-icon.png"
            }
        },
        "breadcrumb": {
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Главная",
                    "item": "https://farm429.online/"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Получение API токена",
                    "item": "https://farm429.online/get_token.php"
                }
            ]
        },
        "mainEntity": {
            "@type": "SoftwareApplication",
            "name": "Система управления умной фермой ФитоДомик",
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Все",
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "RUB"
            }
        }
    }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2E7D32;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
            --text-color: #333;
            --bg-color: #f5f5f5;
            --card-bg: white;
            --shadow-color: rgba(0,0,0,0.1);
            --border-color: #e0e0e0;
        }
        [data-theme="dark"] {
            --primary-color: #4CAF50;
            --primary-light: #66BB6A;
            --primary-dark: #2E7D32;
            --text-color: #f5f5f5;
            --bg-color: #1a1a1a;
            --card-bg: #2d2d2d;
            --shadow-color: rgba(0,0,0,0.3);
            --border-color: #404040;
        }
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 4px 20px var(--shadow-color);
            border: 1px solid var(--border-color);
            position: relative;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            position: relative;
        }
        h1 {
            color: var(--primary-color);
            font-size: 28px;
            margin: 0;
            flex-grow: 1;
            padding-right: 25px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .info-block {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: var(--bg-color);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-color);
        }
        .label {
            font-weight: 500;
            color: var(--text-color);
            opacity: 0.8;
        }
        .value {
            font-weight: 400;
            color: var(--text-color);
            max-width: 350px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .token-block {
            background: var(--primary-light);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            position: relative;
            border: 1px solid var(--primary-dark);
        }
        .copy-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
        }
        .copy-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .status {
            background: var(--primary-color);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            text-align: center;
        }
        .device-status {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background: var(--bg-color);
            border-radius: 8px;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .status-on {
            background: var(--primary-light);
            box-shadow: 0 0 10px var(--primary-light);
        }
        .status-off {
            background: #F44336;
            box-shadow: 0 0 10px #F44336;
        }
        .theme-toggle {
            position: static;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
            box-shadow: 0 2px 5px var(--shadow-color);
            min-width: 160px;
            height: 42px;
            flex-shrink: 0;
        }
        .theme-toggle:hover {
            background: var(--primary-dark);
        }
        .theme-icon {
            font-size: 18px;
        }
        .footer-info {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-color);
            opacity: 0.7;
        }
        /* Для API токена, чтобы он корректно обрезался */
        .token-value {
            font-weight: 400;
            color: var(--text-color);
            max-width: 350px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
        }
        @media (max-width: 600px) {
            .container {
                margin: 20px;
                padding: 15px;
            }
            .header-container {
                flex-direction: column;
                align-items: stretch;
            }
            h1 {
                margin-bottom: 15px;
                text-align: center;
            }
            .theme-toggle {
                width: 100%;
            }
        }
    </style>
</head>
<body itemscope itemtype="https://schema.org/WebPage">
    <div class="container">
        <div class="header-container">
            <h1 itemprop="headline">ФитоДомик - Получение токена</h1>
            <!-- Кнопка смены темы в правой части -->
            <button class="theme-toggle" id="theme-toggle">
                <span class="theme-icon">🌓</span>
                <span>Светлая тема</span>
            </button>
        </div>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, api_token FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user): ?>
                <div class="info-block" itemscope itemtype="https://schema.org/Person">
                    <div class="info-row">
                        <span class="label">ID пользователя:</span>
                        <span class="value" itemprop="identifier"><?php echo htmlspecialchars($user['id']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Имя пользователя:</span>
                        <span class="value" itemprop="alternateName"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Имя:</span>
                        <span class="value"><span itemprop="givenName"><?php echo htmlspecialchars($user['first_name']); ?></span> <span itemprop="familyName"><?php echo htmlspecialchars($user['last_name']); ?></span></span>
                    </div>
                    <div class="token-block">
                        <div class="info-row">
                            <span class="label">API токен:</span>
                            <span class="token-value" id="token" itemprop="accessCode" title="<?php echo htmlspecialchars($user['api_token']); ?>"><?php echo htmlspecialchars($user['api_token']); ?></span>
                        </div>
                        <button class="copy-btn" onclick="copyToken()">Скопировать токен</button>
                    </div>
                </div>
                <?php
                $stmt = $pdo->prepare("
                    SELECT lamp_state, curtains_state, created_at 
                    FROM sensor_data 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$user_id]);
                $states = $stmt->fetch(PDO::FETCH_ASSOC);
                $last_update = $states ? date('d.m.Y H:i:s', strtotime($states['created_at'])) : 'Нет данных';
                if ($states): ?>
                    <div class="info-block" itemscope itemtype="https://schema.org/IoTSensor">
                        <h3 itemprop="name">Состояния устройств</h3>
                        <meta itemprop="dateModified" content="<?php echo date('c', strtotime($states['created_at'])); ?>">
                        <div class="device-status">
                            <div class="status-indicator <?php echo $states['lamp_state'] ? 'status-on' : 'status-off'; ?>"></div>
                            <span itemprop="value">Лампа: <?php echo $states['lamp_state'] ? 'Включена' : 'Выключена'; ?></span>
                        </div>
                        <div class="device-status">
                            <div class="status-indicator <?php echo $states['curtains_state'] ? 'status-on' : 'status-off'; ?>"></div>
                            <span itemprop="value">Шторы: <?php echo $states['curtains_state'] ? 'Закрыты' : 'Открыты'; ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="info-block">
                        <h3>Состояния устройств</h3>
                        <p>Нет данных о состоянии устройств</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="info-block">
                    <p>Пользователь не найден.</p>
                </div>
            <?php endif;
        } catch (Exception $e) {
            echo '<div class="info-block"><p>Ошибка: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
        } 
        ?>
        <div id="copyStatus" class="status">Токен скопирован в буфер обмена!</div>
        <!-- Блок с информацией о последнем обновлении -->
        <div class="footer-info" itemprop="dateModified" content="<?php echo date('c'); ?>">
            Последнее обновление: <?php echo date('d.m.Y H:i:s'); ?>
        </div>
    </div>
    <!-- Подключаем файлы скриптов -->
    <script>
        function copyToken() {
            const token = document.getElementById('token').textContent;
            navigator.clipboard.writeText(token).then(() => {
                const status = document.getElementById('copyStatus');
                status.style.display = 'block';
                setTimeout(() => {
                    status.style.display = 'none';
                }, 2000);
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            if (!themeToggle) return;
            const themeIcon = themeToggle.querySelector('.theme-icon');
            const themeName = themeToggle.querySelector('span:not(.theme-icon)');
            const html = document.documentElement;
            const savedTheme = localStorage.getItem('theme') || 'light';
            html.setAttribute('data-theme', savedTheme);
            updateThemeDisplay(savedTheme);
            themeToggle.addEventListener('click', function() {
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeDisplay(newTheme);
            });
            function updateThemeDisplay(theme) {
                if (themeIcon) {
                    themeIcon.textContent = theme === 'light' ? '🌙' : '☀️';
                }
                if (themeName) {
                    themeName.textContent = theme === 'light' ? 'Темная тема' : 'Светлая тема';
                }
            }
        });
    </script>
</body>
</html> 