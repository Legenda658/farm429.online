<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/headers.php';
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (empty($first_name)) $errors[] = "Имя обязательно для заполнения";
    if (empty($last_name)) $errors[] = "Фамилия обязательна для заполнения";
    if (empty($username)) $errors[] = "Никнейм обязателен для заполнения";
    if (empty($password)) $errors[] = "Пароль обязателен для заполнения";
    if ($password !== $confirm_password) $errors[] = "Пароли не совпадают";
    if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Никнейм должен содержать только латинские буквы, цифры и знак подчеркивания, длина от 3 до 20 символов";
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "Этот никнейм уже занят";
        } else {
            // Проверяем наличие верификации для этого никнейма
            $stmt = $pdo->prepare("SELECT id FROM telegram_verifications WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Этот никнейм уже зарегистрирован в системе верификации. Пожалуйста, выберите другой.";
            } else {
                $_SESSION['temp_user'] = [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT)
                ];
                header("Location: telegram_verify.php");
                exit();
            }
        }
    }
}
function transliterate($string) {
    $converter = array(
        'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
        'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
        'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
        'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
        'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
        'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
        'э' => 'e',    'ю' => 'yu',   'я' => 'ya',
        'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
        'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
        'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
        'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
        'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
        'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya'
    );
    $string = strtr($string, $converter);
    $string = strtolower($string);
    $string = preg_replace('/[^-a-z0-9_]+/', '', $string);
    $string = trim($string, '-');
    return $string;
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - ФитоДомик</title>
    <meta name="description" content="Регистрация в системе управления умной фермой ФитоДомик. Создайте аккаунт для доступа к полному функционалу управления вашей умной фермой.">
    <meta name="keywords" content="регистрация, создать аккаунт, умная ферма, фитодомик, управление фермой, новый пользователь">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="https://farm429.online/authentication/register.php">
    <meta property="og:title" content="Регистрация в системе ФитоДомик">
    <meta property="og:description" content="Создайте аккаунт для доступа к полному функционалу управления вашей умной фермой ФитоДомик.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://farm429.online/authentication/register.php">
    <meta property="og:image" content="https://farm429.online/icon/apple-touch-icon.png">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="ФитоДомик">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Регистрация в системе ФитоДомик">
    <meta name="twitter:description" content="Создайте аккаунт для доступа к полному функционалу управления вашей умной фермой.">
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
        "name": "Регистрация в системе ФитоДомик",
        "description": "Страница регистрации для доступа к управлению умной фермой ФитоДомик",
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
                    "name": "Регистрация",
                    "item": "https://farm429.online/authentication/register.php"
                }
            ]
        },
        "mainEntity": {
            "@type": "RegisterAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "https://farm429.online/authentication/register.php",
                "actionPlatform": [
                    "http://schema.org/DesktopWebPlatform",
                    "http://schema.org/MobileWebPlatform"
                ]
            },
            "result": {
                "@type": "EntryPoint",
                "urlTemplate": "https://farm429.online/authentication/telegram_verify.php"
            }
        }
    }
    </script>
</head>
<body>
    <div class="auth-container">
        <button id="theme-toggle" class="theme-toggle auth-theme-toggle">
            <span class="theme-icon">🌙</span>
        </button>
        <div class="auth-form">
            <h2>Регистрация в ФитоДомик</h2>
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="first_name">Имя</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Фамилия</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Никнейм</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           pattern="[a-zA-Z0-9_]{3,20}" 
                           title="Только латинские буквы, цифры и знак подчеркивания, длина от 3 до 20 символов"
                           required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Подтверждение пароля</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="profile-actions">
                    <button type="submit" class="auth-button">Продолжить регистрацию</button>
                    <a href="login.php" class="auth-button secondary">Уже есть аккаунт? Войти</a>
                    <a href="../index.php" class="auth-button secondary return-profile">Вернуться на главную</a>
                </div>
            </form>
        </div>
    </div>
    <script src="js/theme.js"></script>
</body>
</html>