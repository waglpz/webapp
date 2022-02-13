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
            echo 'Reset DB in PRODUCTION not allowed!';
            echo \PHP_EOL;

            return;
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            $tables = $this->pdo->yieldCol('SHOW TABLES');

            foreach ($tables as $table) {
                if ($table === '__migrations') {
                    continue;
                }

                $this->pdo->fetchAffected(\sprintf('DROP TABLE IF EXISTS `%s`', $table));
            }

            echo 'Dropped Tables.' . \PHP_EOL;
            $this->pdo->fetchAffected('TRUNCATE __migrations');
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

            $this->pdo->commit();
            echo 'Reset migrations.' . \PHP_EOL;
            echo 'Database was successfully reseted.';
            echo \PHP_EOL;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            echo 'Error occurred!' . \PHP_EOL;
            echo $exception->getMessage() . \PHP_EOL;
            echo 'Rollback done!';
        }
    }
}
