<?php
require_once 'config/database.php';
session_start();
$error = null;
$success = false;
$code = isset($_GET['code']) ? trim($_GET['code']) : '';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
if (!empty($code)) {
    try {
        if (strlen($code) !== 8) {
            $error = 'Неверный формат кода режима';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM preset_modes WHERE share_code = ?");
            $stmt->execute([$code]);
            $mode = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$mode) {
                $error = 'Режим с указанным кодом не найден';
            } else if ($mode['user_id'] == $_SESSION['user_id']) {
                $error = 'Этот режим уже принадлежит вам';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO preset_modes 
                    (user_id, name, description, temperature, tolerance, 
                     humidity, humidity_tolerance, light_hours, 
                     light_start, light_end, created_at) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $newName = $mode['name'] . ' (импорт)';
                $stmt->execute([
                    $_SESSION['user_id'],
                    $newName,
                    $mode['description'],
                    $mode['temperature'],
                    $mode['tolerance'],
                    $mode['humidity'],
                    $mode['humidity_tolerance'],
                    $mode['light_hours'],
                    $mode['light_start'],
                    $mode['light_end']
                ]);
                $success = true;
            }
        }
    } catch (PDOException $e) {
        error_log('Ошибка импорта режима: ' . $e->getMessage());
        $error = 'Произошла ошибка при импорте режима';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Импорт режима - ФитоДомик</title>
    <meta name="description" content="Импортируйте готовые режимы выращивания в вашу умную ферму ФитоДомик. Оптимизируйте параметры роста для различных типов растений с помощью проверенных шаблонов.">
    <meta name="keywords" content="ФитоДомик, умная ферма, импорт режима, шаблоны выращивания, параметры растений, оптимизация выращивания, обмен режимами">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="https://farm429.online/import-mode.php">
    <!-- Метатеги Open Graph для социальных сетей -->
    <meta property="og:title" content="Импорт режима - ФитоДомик">
    <meta property="og:description" content="Импортируйте проверенные режимы выращивания растений в вашу умную ферму ФитоДомик">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://farm429.online/import-mode.php">
    <meta property="og:image" content="https://farm429.online/security/image.php?file=icon/apple-touch-icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="apple-touch-icon" sizes="180x180" href="security/image.php?file=icon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="security/image.php?file=icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="security/image.php?file=icon/favicon-16x16.png">
    <link rel="manifest" href="security/manifest.php?file=site.webmanifest">
    <link rel="shortcut icon" href="security/image.php?file=icon/favicon.ico">
    <!-- Структурированные данные Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Импорт режима - ФитоДомик",
        "description": "Страница для импорта пользовательских режимов выращивания растений в системе ФитоДомик",
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
                    "name": "Импорт режима",
                    "item": "https://farm429.online/import-mode.php"
                }
            ]
        },
        "mainEntity": {
            "@type": "SoftwareApplication",
            "name": "ФитоДомик - Импорт режима",
            "applicationCategory": "Utility",
            "operatingSystem": "All"
        }
    }
    </script>
    <style>
        .import-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            background-color: var(--card-bg);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .import-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            text-align: center;
        }
        .import-icon {
            font-size: 48px;
            display: block;
            text-align: center;
            margin-bottom: 20px;
        }
        .import-message {
            margin-bottom: 30px;
            font-size: 18px;
            text-align: center;
        }
        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
    </style>
</head>
<body itemscope itemtype="https://schema.org/WebPage">
    <?php include 'components/navbar.php'; ?>
    <div class="container" itemprop="mainContentOfPage">
        <div class="import-container" itemscope itemtype="https://schema.org/SoftwareApplication">
            <?php if ($success): ?>
                <span class="import-icon">✅</span>
                <h2 class="import-title" itemprop="name">Режим успешно импортирован</h2>
                <p class="import-message" itemprop="description">Режим был успешно добавлен в ваши пресеты.</p>
                <div class="button-container">
                    <a href="index.php" class="btn btn-primary">На главную</a>
                    <a href="farmsite.php" class="btn btn-success">Перейти к режимам</a>
                </div>
            <?php elseif ($error): ?>
                <span class="import-icon">❌</span>
                <h2 class="import-title" itemprop="name">Ошибка импорта</h2>
                <p class="import-message" itemprop="description"><?php echo $error; ?></p>
                <div class="button-container">
                    <a href="index.php" class="btn btn-primary">На главную</a>
                    <a href="farmsite.php" class="btn btn-outline-secondary">Перейти к режимам</a>
                </div>
            <?php else: ?>
                <span class="import-icon">🔍</span>
                <h2 class="import-title" itemprop="name">Импорт режима</h2>
                <p class="import-message" itemprop="description">Для импорта режима введите код:</p>
                <form method="get" action="import-mode.php" class="mb-4" itemprop="potentialAction" itemscope itemtype="https://schema.org/SearchAction">
                    <meta itemprop="target" content="https://farm429.online/import-mode.php?code={code}"/>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="code" placeholder="Введите 8-символьный код" maxlength="8" required itemprop="query-input">
                        <button class="btn btn-primary" type="submit">Импортировать</button>
                    </div>
                </form>
                <div class="text-center">
                    <a href="farmsite.php" class="btn btn-outline-secondary">Вернуться назад</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 