<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\UI\Cli;

use Aura\Sql\ExtendedPdoInterface;
use PHPUnit\Framework\TestCase;
use Waglpz\Webapp\UI\Cli\CliError;
use Waglpz\Webapp\UI\Cli\DbMigrations;

class DbMigrationsTest extends TestCase
{
    /** @test */
    public function noMigrationsForExecution(): void
    {
        $dirName = '/tmp/' . \uniqid();
        \mkdir($dirName, 0777, true);
        $options    = [
            'migrations' => $dirName,
            'usage'      => [],
        ];
        $connection = $this->createMock(ExtendedPdoInterface::class);
        $connection->expects(self::never())->method('beginTransaction');
        $connection->expects(self::once())->method('fetchCol')->willReturn([]);
        $connection->expects(self::never())->method('fetchAffected');
        $connection->expects(self::never())->method('exec');
        $connection->expects(self::never())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $command = new DbMigrations($options);
        $command->setConnection($connection);
        $_SERVER['argv'][2] = 'migrate';

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Nothing to do, no new migrations to execute.');
        $command();
    }

    /** @test */
    public function check(): void
    {
        $options = [
            'migrations' => '',
            'usage'      => [],
        ];

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Migration directory not writeable or does not exists "".');
        (new DbMigrations($options))();
    }

    /** @test */
    public function executeMigrations(): void
    {
        $options = [
            'migrations' => __DIR__ . '/migrations-stubs',
            'usage'      => [],
        ];

        $migrations = [1605646638];

        $connection = $this->createMock(ExtendedPdoInterface::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('fetchCol')
                   ->with('SELECT migration FROM __migrations ORDER BY migration')
                   ->willReturn($migrations);
        $connection->expects(self::once())->method('fetchAffected')->willReturn(1);
        $connection->expects(self::once())->method('exec')
                   ->with('INSERT INTO __migrations (migration) VALUES (1605646639)')
                   ->willReturn(1);
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $command = new DbMigrations($options);
        $command->setConnection($connection);
        $_SERVER['argv'][2] = 'migrate';

        $output = $command()->__toString();
        self::assertSame(
            'Result migrations:
  Affected rows #1
  Applied migrations #1
',
            $output
        );
    }

    /** @test */
    public function rollBackMigrations(): void
    {
        $options = [
            'migrations' => __DIR__ . '/migrations-stubs',
            'usage'      => [],
        ];

        $migrations = [1605646638];

        $connection = $this->createMock(ExtendedPdoInterface::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('fetchCol')
                   ->with('SELECT migration FROM __migrations ORDER BY migration')
                   ->willReturn($migrations);
        $connection->expects(self::once())->method('fetchAffected')->willReturn(1);
        $connection->expects(self::once())->method('exec')
                   ->with('INSERT INTO __migrations (migration) VALUES (1605646639)')
                   ->willThrowException(new \Exception('Test Exception message'));
        $connection->expects(self::never())->method('commit');
        $connection->expects(self::once())->method('rollBack');

        $command = new DbMigrations($options);
        $command->setConnection($connection);
        $_SERVER['argv'][2] = 'migrate';

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Test Exception message');
        $command();
    }

    /** @test */
    public function generateNewMigration(): void
    {
        $options            = [
            'migrations' => '/tmp',
            'usage'      => [],
        ];
        $_SERVER['argv'][2] = 'generate';
        (new DbMigrations($options))();
        self::assertFileExists('/tmp/migration-' . \time() . '-up.sql');
        self::assertFileExists('/tmp/migration-' . \time() . '-down.sql');
    }

    /** @test */
    public function nochNichtImplementierteMethode(): void
    {
        $options            = [
            'migrations' => '/tmp',
            'usage'      => [],
        ];
        $_SERVER['argv'][2] = 'up';

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method "up" not yet implemented.');
        (new DbMigrations($options))();
    }

    /** @test */
    public function usageWirdAnzeigt(): void
    {
        $options            = [
            'migrations' => '/tmp',
            'usage'      => [],
        ];
        $_SERVER['argv'][2] = 'wrong';

        $this->expectException(CliError::class);
        $this->expectExceptionMessageMatches('/Usage:/');
        (new DbMigrations($options))();
    }
}
