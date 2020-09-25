#!/bin/usr/env bash
<?php

declare(strict_types=1);

use Aura\Sql\ExtendedPdo;
use Waglpz\Webapp\App;
use Waglpz\Webapp\DbConnection;

$prepend = \PHP_EOL . '[!] ';
$append  = \PHP_EOL . \PHP_EOL;
$usage   = $prepend
    . 'Operation name one of "up", "down", "migrate" or "generate" as first argument expected.'
    . $append;

if (! isset($_SERVER['argv'][1])) {
    echo $usage;
    exit(1);
}

$operation = \strtolower($_SERVER['argv'][1]);
if ($operation !== 'up'
    && $operation !== 'down'
    && $operation !== 'migrate'
    && $operation !== 'generate'
) {
    echo $usage;
    exit(1);
}

$app = include 'cliApp.php';
\assert($app instanceof App);

$getConnection = static function () use ($prepend, $append) : ExtendedPdo {
    /* phpcs:disable */
    try {
        return (new class {
            use DbConnection;
        })->getConnection();
    } catch (\Throwable $exception) {
        echo $prepend . "Database ERROR: " . $exception->getMessage() . $append;
        exit(1);
    }
    /* phpcs:enable */
};

/** @return array<mixed> */
$allMigrations = static function (\DirectoryIterator $directoryIterator) : array {
    $allMigrations = [];
    foreach ($directoryIterator as $file) {
        if (\strcasecmp($file->getExtension(), 'sql') !== 0) {
            continue;
        }

        $fileName = $file->getBasename('.sql');

        [$prefix, $time, $operation] = \explode('-', $fileName);

        $allMigrations[$time][$operation] = \file_get_contents($file->getPathname());
    }

    return $allMigrations;
};

/**
 * @param array<mixed> $allMigrations
 *
 * @return array<mixed>
 */
$newMigrations = static function (ExtendedPdo $db, array $allMigrations) : array {
    $oldMigrations = $db->fetchCol(
        'SELECT migration FROM __migrations ORDER BY migration'
    );

    $newMigrations = \array_filter(
        $allMigrations,
        static fn($migrationTime) => ! in_array((string) $migrationTime, $oldMigrations, true),
        ARRAY_FILTER_USE_KEY
    );

    \ksort($newMigrations);

    return $newMigrations;
};

$migrationsDir = $app::hasConfig('migrations') ? $app::getConfig('migrations') : '';
if (! is_dir($migrationsDir) || ! is_writable($migrationsDir)) {
    echo $prepend
        . 'Migration directory not writeable or does not exists "'
        . $migrationsDir . '"'
        . $append;
}

$db                = $getConnection();
$directoryIterator = new DirectoryIterator($migrationsDir);
$allMigrations     = $allMigrations($directoryIterator);
$newMigrations     = $newMigrations($db, $allMigrations);

$operations = [
    'migrate'  => static function (ExtendedPdo $db, array $newMigrations) use ($prepend, $append) : void {
        $affectedRows    = 0;
        $insertMigration = 0;
        $db->beginTransaction();
        try {
            foreach ($newMigrations as $migrationTime => $migration) {
                $affectedRows    += $db->fetchAffected($migration['up']);
                $insertMigration += $db->exec(
                    'INSERT INTO __migrations (migration) VALUES (' . $migrationTime . ')'
                );
            }

            echo \PHP_EOL;
            echo '[+] Affected rows #' . $affectedRows . \PHP_EOL;
            echo '[+] Applied migrations #' . $insertMigration . \PHP_EOL;
            echo \PHP_EOL;
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            echo $prepend . $exception->getMessage() . $append;
            exit(1);
        }
    },
    'generate' => static function () use ($migrationsDir, $append) : void {
        $time     = \time();
        $fileName = 'migration-' . $time . '-up.sql';
        \touch($migrationsDir . '/' . $fileName);
        echo \PHP_EOL . '[+] Created migration "' . $fileName . '"' . $append;
        $fileName = 'migration-' . $time . '-down.sql';
        \touch($migrationsDir . '/' . $fileName);
        echo \PHP_EOL . '[+] Created migration "' . $fileName . '"' . $append;
    },
];

$operations[$operation]($db, $newMigrations);
