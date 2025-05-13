<?php
require_once 'security/headers.php';
header("HTTP/1.0 404 Not Found");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена - ФитоДомик</title>
    <!-- SEO метатеги -->
    <meta name="description" content="Ошибка 404 - запрашиваемая страница на сайте ФитоДомик не найдена. Перейдите на главную страницу для доступа к управлению умной фермой.">
    <meta name="keywords" content="ошибка 404, страница не найдена, фитодомик, умная ферма">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="https://farm429.online/404.php">
    <!-- Open Graph метатеги для соцсетей -->
    <meta property="og:title" content="Страница не найдена - ФитоДомик">
    <meta property="og:description" content="Запрашиваемая страница не существует или была перемещена по другому адресу.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://farm429.online/404.php">
    <meta property="og:image" content="https://farm429.online/security/image.php?file=icon/apple-touch-icon.png">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="ФитоДомик">
    <!-- Twitter Card метатеги -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Страница не найдена - ФитоДомик">
    <meta name="twitter:description" content="Запрашиваемая страница не существует или была перемещена по другому адресу.">
    <meta name="twitter:image" content="https://farm429.online/security/image.php?file=icon/apple-touch-icon.png">
    <link rel="stylesheet" href="security/css.php?file=styles.css">
    <!-- Добавляем иконки -->
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
        "name": "Страница не найдена - ФитоДомик",
        "description": "Ошибка 404 - запрашиваемая страница не существует или была перемещена по другому адресу",
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
                    "name": "Ошибка 404",
                    "item": "https://farm429.online/404.php"
                }
            ]
        }
    }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        });
    </script>
    <style>
        .error-container {
            max-width: 800px;
            margin: 100px auto;
            text-align: center;
            padding: 30px;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .error-code {
            font-size: 120px;
            color: var(--primary-color);
            margin: 0;
            line-height: 1;
        }
        .error-title {
            font-size: 32px;
            margin: 10px 0 30px;
        }
        .error-message {
            font-size: 18px;
            margin-bottom: 40px;
            color: var(--text-color);
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            font-weight: 500;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        [data-theme="dark"] .error-message {
            color: #ffffff;
            background-color: rgba(76, 175, 80, 0.15);
            border-color: rgba(76, 175, 80, 0.5);
        }
        [data-theme="light"] .error-message {
            color: #333333;
            background-color: rgba(76, 175, 80, 0.05);
            border-color: rgba(76, 175, 80, 0.3);
        }
        .back-button {
            display: inline-block;
            padding: 12px 30px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #3d8b40;
        }
        @media (max-width: 600px) {
            .error-code {
                font-size: 100px;
            }
            .error-title {
                font-size: 24px;
            }
            .error-container {
                margin: 50px 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body itemscope itemtype="https://schema.org/WebPage">
    <div class="error-container" itemscope itemtype="https://schema.org/AboutPage">
        <h1 class="error-code" itemprop="name">404</h1>
        <h2 class="error-title" itemprop="headline">Страница не найдена</h2>
        <p class="error-message" itemprop="description">Запрашиваемая страница не существует или была перемещена по другому адресу.</p>
        <a href="https://farm429.online/index.php" class="back-button" itemprop="mainEntityOfPage">Вернуться на главную</a>
        <meta itemprop="dateModified" content="<?php echo date('c'); ?>">
    </div>
</body>
</html> 