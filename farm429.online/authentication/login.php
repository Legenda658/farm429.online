<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/headers.php';
if (isLoggedIn()) {
    echo '<script>window.location.href = "../index.php";</script>';
    exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['telegram'] = $user['telegram'];
            echo '<script>window.location.href = "../index.php";</script>';
            exit();
        } else {
            $error = 'Неверное имя пользователя или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - ФитоДомик</title>
    <meta name="description" content="Вход в систему управления умной фермой ФитоДомик. Авторизуйтесь для доступа к полному функционалу управления вашей умной фермой.">
    <meta name="keywords" content="вход, логин, авторизация, умная ферма, фитодомик, управление фермой">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="https://farm429.online/authentication/login.php">
    <meta property="og:title" content="Вход в систему ФитоДомик">
    <meta property="og:description" content="Авторизуйтесь для доступа к полному функционалу управления вашей умной фермой ФитоДомик.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://farm429.online/authentication/login.php">
    <meta property="og:image" content="https://farm429.online/icon/apple-touch-icon.png">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="ФитоДомик">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Вход в систему ФитоДомик">
    <meta name="twitter:description" content="Авторизуйтесь для доступа к полному функционалу управления вашей умной фермой.">
    <meta name="twitter:image" content="https://farm429.online/icon/apple-touch-icon.png">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="apple-touch-icon" sizes="180x180" href="../icon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../icon/favicon-16x16.png">
    <link rel="manifest" href="../icon/site.webmanifest">
    <link rel="shortcut icon" href="../icon/favicon.ico">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Вход в систему - ФитоДомик",
        "description": "Страница авторизации для доступа к управлению умной фермой ФитоДомик",
        "publisher": {
            "@type": "Organization",
            "name": "ФитоДомик",
            "logo": {
                "@type": "ImageObject",
                "url": "https://farm429.online/icon/apple-touch-icon.png"
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
                    "name": "Вход в систему",
                    "item": "https://farm429.online/authentication/login.php"
                }
            ]
        },
        "mainEntity": {
            "@type": "LoginAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "https://farm429.online/authentication/login.php",
                "actionPlatform": [
                    "http://schema.org/DesktopWebPlatform",
                    "http://schema.org/MobileWebPlatform"
                ]
            },
            "result": {
                "@type": "EntryPoint",
                "urlTemplate": "https://farm429.online/index.php"
            }
        }
    }
    </script>
</head>
<body itemscope itemtype="https://schema.org/WebPage">
    <div class="auth-container">
        <button id="theme-toggle" class="theme-toggle auth-theme-toggle">
            <span class="theme-icon">🌙</span>
        </button>
        <div class="auth-form" itemprop="mainEntity" itemscope itemtype="https://schema.org/UserInteraction">
            <h2 itemprop="name">Вход в ФитоДомик</h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])): ?>
                <div class="success-message">Регистрация успешно завершена! Теперь вы можете войти.</div>
            <?php endif; ?>
            <form method="POST" action="" itemscope itemtype="https://schema.org/LoginAction">
                <div class="form-group">
                    <label for="username">Никнейм</label>
                    <input type="text" id="username" name="username" required itemprop="name">
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="profile-actions">
                    <button type="submit" class="auth-button">Войти</button>
                    <a href="register.php" class="auth-button secondary" itemprop="potentialAction" itemscope itemtype="https://schema.org/RegisterAction">Регистрация</a>
                    <a href="../index.php" class="auth-button secondary return-profile">Вернуться на главную</a>
                </div>
                <meta itemprop="target" content="https://farm429.online/authentication/login.php">
                <meta itemprop="result" content="https://farm429.online/index.php">
            </form>
            <meta itemprop="dateModified" content="<?php echo date('c'); ?>">
        </div>
    </div>
    <script src="js/theme.js"></script>
</body>
</html>