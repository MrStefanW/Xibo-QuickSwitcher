<?php
require_once PROJECT_ROOT . '/custom/QuickSwitcher/QuickSwitcherMiddleware.php';
require_once PROJECT_ROOT . '/custom/QuickSwitcher/QuickSwitcherController.php';

$middleware = $middleware ?? [];
$middleware[] = new \Xibo\Custom\QuickSwitcher\QuickSwitcherMiddleware();

