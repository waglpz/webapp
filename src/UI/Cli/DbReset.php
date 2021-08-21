<?php

declare(strict_types=1);

namespace Waglpz\Webapp\UI\Cli;

use Aura\Sql\ExtendedPdoInterface;

final class DbReset
{
    private ExtendedPdoInterface $pdo;
    private string $migrations;

    /** @param array<string> $options */
    public function __construct(ExtendedPdoInterface $pdo, array $options)
    {
        $this->migrations = $options['migrations'];
        $this->pdo        = $pdo;
    }

    public function __invoke(): void
    {
        if (\APP_ENV === 'prod') {
            echo 'Reset DB in PRODUCTION not Allowed!';
            echo \PHP_EOL;

            return;
        }

        $this->pdo->beginTransaction();
        try {
            $finder = new \DirectoryIterator($this->migrations);
            $files  = [];
            /** @phpstan-var \SplFileInfo $file */
            foreach ($finder as $file) {
                if (\strpos($file->getBasename(), '-down.sql') === false) {
                    continue;
                }

                $files[] = $file->getBasename();
            }

            \rsort($files);

            foreach ($files as $file) {
                $stmt = \file_get_contents($this->migrations . '/' . $file);
                \assert(\is_string($stmt));
                $this->pdo->fetchAffected($stmt);
            }

            echo 'Dropped Tables' . \PHP_EOL;
            $this->pdo->fetchAffected('TRUNCATE __migrations');
            $this->pdo->commit();
            echo 'Reset migrations.' . \PHP_EOL;
            echo 'Database was reset successfully.';
            echo \PHP_EOL;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            echo 'Error occurred!' . \PHP_EOL;
            echo $exception->getMessage() . \PHP_EOL;
            echo 'Rollback done!';
        }
    }
}
