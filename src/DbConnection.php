<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;

/**
 * @codeCoverageIgnore
 */
trait DbConnection
{
    private ExtendedPdoInterface $connection;

    public function setConnection(ExtendedPdoInterface $connection): void
    {
        $this->connection = $connection;
    }

    public function getConnection(): ExtendedPdoInterface
    {
        if (isset($this->connection)) {
            return $this->connection;
        }

        if (! App::hasConfig('db')) {
            throw new \RuntimeException('Database connection not properly configured. Check config key "db".');
        }

        [
            'dsn'      => $dsn,
            'username' => $username,
            'password' => $password,
            'options'  => $options,
            'queries'  => $queries,
            'profiler' => $profiler,

        ] = App::getConfig()['db'];

        return new ExtendedPdo(
            $dsn,
            $username,
            $password,
            $options,
            $queries,
            $profiler
        );
    }
}
