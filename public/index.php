<?php
require_once __DIR__ . '/../router.php';
global $route;

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
}

ob_start();
require_once __DIR__ . "/../routes/$route.php";
$content = ob_get_clean();

ob_start();
require_once __DIR__ . "/../components/headerjs.php";
$headerjs = ob_get_clean();

ob_start();
require_once __DIR__ . "/../components/bottomjs.php";
$bottomjs = ob_get_clean();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
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
    <!-- External Deps -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link rel="manifest" href="/assets/pwa/site.webmanifest?v=17">
    <link rel="shortcut icon" href="/assets/pwa/favicon.ico?v=3" type="image/x-icon">
    <script src="https://unpkg.com/htmx.org@2.0.4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Local Deps -->
    <link rel="stylesheet" href="/styles.css?v=831">
    <script src="/assets/wasm/sql-wasm.js"></script>
    <?= $headerjs ?>
    <!-- TODO: Implement google analytics -->
    <!-- TODO: install diaglog and button -->
    <!-- TODO: add fmt and other news -->
    <!-- TODO: add links filter -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js?v=77')
                    .then((registration) => {})
                    .catch((registrationError) => {});
            });
        }
    </script>
    <link rel="prefetch" href="/offline?v=2">
</head>

<body>
    <div id="loading"></div>
    <input x-data="{ 'darkmode' : $persist(false)}" x-model='darkmode' type="checkbox" id="dark-toggle" class="dark-toggle-checkbox">
    <div id="background"></div>
    
    <header>
        <a class="logo" hx-get="/" hx-target="#content" hx-swap="outerHTML" hx-select="#content">
            iheb.tn
        </a>
        <label for="dark-toggle" class="dark-toggle">
        </label>
    </header>
    <div id="progress"></div>
    
    <main id="content">
        <?= $content ?>
    </main>
    <footer>Made with ❤️ by Iheb Chagra</footer>
    <?= $bottomjs ?>
</body>

</html>
