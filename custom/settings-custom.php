<?php
$middleware = $middleware ?? [];
$middleware[] = new \Xibo\Custom\QuickSwitcher\QuickSwitcherMiddleware();

if (!defined('QUICKSWITCHER_OB_STARTED')) {
    define('QUICKSWITCHER_OB_STARTED', true);
    ob_start(function (string $html): string {
        if (!preg_match('/nonce=["\']([^"\']+)["\']/', $html, $matches)) {
            return $html;
        }

        $nonce = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');

        $assets = '
        <link rel="stylesheet" href="/QuickSwitcher/assets/QuickSwitcher.css">
        <script src="/QuickSwitcher/assets/QuickSwitcher.js" nonce="' . $nonce . '"></script>
        ';

        return preg_replace(
            '/<\/head\s*>/i',
            $assets . '</head>',
            $html,
            1
        );
    });
}

