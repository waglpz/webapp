<?php

declare(strict_types=1);

return [
    'dsn'      => 'mysql:host=' . $_SERVER['DB_HOST'] . '-db;dbname=' . $_SERVER['DB_NAME'],
    'username' => $_SERVER['DB_USER'],
    'password' => $_SERVER['DB_PASSWD'],
];
