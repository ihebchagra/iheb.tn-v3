<?php
require_once __DIR__ . '/../routes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($route) {
        case 'analytics':
            require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/$route.php";
            exit;
        break;
        default:
            header('Location: /');
            exit;
        break;
}

ob_start();
require_once __DIR__ . "/../routes/$route.php";
$content = ob_get_clean();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Iheb Chagra</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=no">
    <meta name="description" content="Le site personnel d'Iheb Chagra, ">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="/index.css?v=429">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Dosis&family=Dosis:wght@700&display=swap"
        rel="stylesheet">
    <link rel="manifest" href="/manifest.json?v=16">
    <link rel="shortcut icon" href="/assets/img/icons/favicon.ico?v=1" type="image/x-icon">
    <script src="https://unpkg.com/htmx.org@2.0.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/@marcreichel/alpine-autosize@latest/dist/alpine-autosize.min.js" defer></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script type="module" src="/assets/js/header.js?v=12"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js?v=74')
                    .then((registration) => {})
                    .catch((registrationError) => {});
            });
        }
    </script>
    <link rel="prefetch" href="/offline?v=2">
</head>

<body>
    <p-loading shadow></p-loading>
    <p-go-back shadow></p-go-back>
    <p-drawer shadow></p-drawer>
    <p-install-banner shadow></p-install-banner>
    <div id="background"></div>
    <?php
    if ($_SERVER['HTTP_HOST'] !== 'promety.tn') {
        ?>
        <div id="dev-mode-banner">DEV MODE</div>
    <?php
    }
?>
    <main id="content">
        <div id="role" style="display: none;" data-role=<?= '"' . $user_state . '"' ?>></div>
        <?= $content ?>
    </main>
    <script src="/assets/js/index.js?v=322"></script>
    <?php
include_once __DIR__ . "/assets/js/javascript.php";
?>
</body>

</html>
