<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'security/security_bootstrap.php'; 
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ФитоДомик - Управление фермой</title>
    <!-- SEO метатеги -->
    <meta name="description" content="ФитоДомик - система управления умной фермой для выращивания растений в домашних условиях. Контроль климата, автоматизация полива, мониторинг показателей в реальном времени.">
    <meta name="keywords" content="умная ферма, фитодомик, выращивание растений, домашняя ферма, автоматизация теплицы, iot сельское хозяйство, умное растениеводство, контроль климата">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://farm429.online/index.php">
    <!-- Open Graph метатеги для соцсетей -->
    <meta property="og:title" content="ФитоДомик - Система управления умной фермой">
    <meta property="og:description" content="Управляйте своей умной фермой с любого устройства. Контроль климата, автоматизация полива, мониторинг показателей в реальном времени.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://farm429.online/index.php">
    <meta property="og:image" content="https://farm429.online/security/image.php?file=icon/apple-touch-icon.png">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="ФитоДомик">
    <!-- Twitter Card метатеги -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="ФитоДомик - Система управления умной фермой">
    <meta name="twitter:description" content="Управляйте своей умной фермой с любого устройства. Контроль климата, автоматизация полива, мониторинг показателей.">
    <meta name="twitter:image" content="https://farm429.online/security/image.php?file=icon/apple-touch-icon.png">
    <!-- CSS обработчик -->
    <link rel="stylesheet" href="security/css.php?file=styles.css">
    <!-- JavaScript обработчик -->
    <script src="security/js.php?file=theme.js"></script>
    <!-- Иконки обработчик -->
    <link rel="apple-touch-icon" sizes="180x180" href="security/image.php?file=icon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="security/image.php?file=icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="security/image.php?file=icon/favicon-16x16.png">
    <link rel="manifest" href="security/manifest.php?file=site.webmanifest">
    <link rel="shortcut icon" href="security/image.php?file=icon/favicon.ico">
    <!-- Структурированные данные Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "ФитоДомик - Система управления умной фермой",
        "applicationCategory": "IoT, SmartHome, FarmManagement",
        "description": "Система управления умным устройством для выращивания растений в домашних условиях с контролем климата и автоматизацией",
        "operatingSystem": "All",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "RUB"
        },
        "publisher": {
            "@type": "Organization",
            "name": "ФитоДомик",
            "logo": {
                "@type": "ImageObject",
                "url": "https://farm429.online/security/image.php?file=icon/apple-touch-icon.png"
            }
        },
        "screenshot": [
            {
                "@type": "ImageObject",
                "url": "https://farm429.online/security/image.php?file=images/dashboard.jpg",
                "caption": "Панель управления ФитоДомик"
            }
        ],
        "potentialAction": {
            "@type": "ViewAction",
            "target": "https://farm429.online/index.php"
        }
    }
    </script>
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            document.querySelector('.theme-icon').textContent = newTheme === 'dark' ? '🌙' : '☀️';
        }
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.querySelector('.theme-icon').textContent = savedTheme === 'dark' ? '🌙' : '☀️';
        });
    </script>
</head>
<body itemscope itemtype="https://schema.org/WebPage">
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="user-info" itemprop="author" itemscope itemtype="https://schema.org/Person">
                    <div class="user-avatar">
                        <?php if ($user && !empty($user['avatar'])): ?>
                            <img src="security/image.php?file=avatars/<?php echo htmlspecialchars($user['avatar']); ?>" alt="Фото профиля" loading="lazy" width="40" height="40" itemprop="image">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <span itemprop="name"><?php echo $user ? strtoupper(substr($user['first_name'], 0, 1)) . strtoupper(substr($user['last_name'], 0, 1)) : 'Г'; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-dropdown">
                        <button class="profile-button">Профиль</button>
                        <div class="dropdown-content">
                            <?php if (!$user): ?>
                                <a href="authentication/login.php">Войти</a>
                                <a href="authentication/register.php">Регистрация</a>
                            <?php else: ?>
                                <a href="authentication/profile.php">Настройки</a>
                                <a href="authentication/logout.php">Выйти</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <h1 class="site-title" itemprop="headline">ФитоДомик</h1>
            <div class="header-right">
                <button onclick="toggleTheme()" class="theme-toggle">
                    <span class="theme-icon">☀️</span>
                </button>
            </div>
        </div>
    </header>
    <main class="container" itemprop="mainContentOfPage">
        <?php 
        $components = [
            'components/farm-status.php',
            'components/farm-settings.php',
            'components/farm-graphs.php',
            'components/alarm-thresholds.php',
            'components/preset-modes.php',
            'components/planting-calendar.php',
            'components/event-log.php'
        ];
        foreach ($components as $component) {
            if (file_exists($component)) {
                include $component;
            }
        }
        ?>
    </main>
    <footer itemprop="contentInfo">
        <!-- Убрано название ФитоДомик -->
        <meta itemprop="dateModified" content="<?php echo date('c'); ?>">
    </footer>
</body>
</html> 