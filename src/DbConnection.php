<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Aura\Sql\ExtendedPdo;

/**
 * @codeCoverageIgnore
 */
trait DbConnection
{
    public function getConnection() : ExtendedPdo
    {
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
