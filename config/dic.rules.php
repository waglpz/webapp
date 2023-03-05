<?php

declare(strict_types=1);

$config       = [];
$sharedConfig = [];

if (PHP_SAPI !== 'cli' || APP_ENV === 'test') {
    if (is_file(__DIR__ . '/container/web.rules.php')) {
        $config = include __DIR__ . '/container/web.rules.php';
    }
}

if (is_file(__DIR__ . '/container/shared.rules.php')) {
    $sharedConfig = include __DIR__ . '/container/shared.rules.php';
}

return array_replace_recursive($sharedConfig, $config);
