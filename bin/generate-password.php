<?php

declare(strict_types=1);

if (! isset($_SERVER['argv'][1])) {
    echo ' Usage: php ' . $_SERVER['argv'][0] . ' "new password string"';
    echo PHP_EOL;
    exit(1);
}

echo PHP_EOL;
echo 'Generated new hash:';
echo PHP_EOL;
echo PHP_EOL;
echo \password_hash($_SERVER['argv'][1], PASSWORD_ARGON2ID);
echo PHP_EOL;
echo PHP_EOL;
