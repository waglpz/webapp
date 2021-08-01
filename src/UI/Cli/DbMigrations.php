<?php

declare(strict_types=1);

namespace Waglpz\Webapp\UI\Cli;

use Aura\Sql\ExtendedPdoInterface;

final class DbMigrations
{
    /** @var array<mixed> */
    private array $options;
    private string $argument;
    private string $message;
    private ExtendedPdoInterface $pdo;

    /** @param array<mixed> $options */
    public function __construct(ExtendedPdoInterface $pdo, array $options)
    {
        $this->pdo     = $pdo;
        $this->options = $options;
        $this->check();
    }

    private function usage(): void
    {
        $message  = 'Operation name one of "up", "down", "migrate" or "generate" as first argument expected.'
            . \PHP_EOL
            . \PHP_EOL;
        $message .= 'Usage:';
        $message .= \PHP_EOL;
        $message .= '  ' . \implode(\PHP_EOL . '  ', $this->options['usage']);

        throw new CliError($message);
    }

    private function processInput(): void
    {
        $operation = \strtolower($_SERVER['argv'][2] ?? '');
        if (
            $operation !== 'up'
            && $operation !== 'down'
            && $operation !== 'migrate'
            && $operation !== 'generate'
        ) {
            $this->usage();
        } else {
            $this->argument = $operation;
        }
    }

    public function __invoke(): self
    {
        $this->processInput();
        $method = $this->argument;
        if (! \method_exists($this, $method)) {
            throw new \BadMethodCallException(\sprintf('Method "%s" not yet implemented.', $method));
        }

        $this->$method();

        return $this;
    }

    /** @return array<mixed> */
    private function allMigrations(\DirectoryIterator $directoryIterator): array
    {
        $allMigrations = [];
        foreach ($directoryIterator as $file) {
            if ($file->getExtension() !== 'sql') {
                continue;
            }

            $fileName = $file->getBasename('.sql');

            [$prefix, $time, $operation] = \explode('-', $fileName);

            $allMigrations[$time][$operation] = \file_get_contents($file->getPathname());
        }

        return $allMigrations;
    }

    /** @return array<mixed> */
    private function newMigrations(): array
    {
        $directoryIterator = new \DirectoryIterator($this->options['migrations']);
        $allMigrations     = $this->allMigrations($directoryIterator);
        $oldMigrations     = $this->pdo->fetchCol('SELECT migration FROM __migrations ORDER BY migration');

        $newMigrations = \array_filter(
            $allMigrations,
            static fn ($migrationTime) => ! \in_array((string) $migrationTime, $oldMigrations, true),
            \ARRAY_FILTER_USE_KEY
        );

        \ksort($newMigrations);

        return $newMigrations;
    }

    private function check(): void
    {
        $migrationsDir = $this->options['migrations'] ?? '';
        if (! \is_dir($migrationsDir) || ! \is_writable($migrationsDir)) {
            $message = 'Migration directory not writeable or does not exists "' . $migrationsDir . '".';

            throw new CliError($message);
        }
    }

    /**
     * @throws \Throwable
     */
    protected function migrate(): void
    {
        $affectedRows    = 0;
        $insertMigration = 0;

        $newMigrations = $this->newMigrations();
        if (\count($newMigrations) < 1) {
            $message = 'Nothing to do, no new migrations to execute.';

            throw new CliError($message);
        }

        $this->pdo->beginTransaction();

        try {
            foreach ($newMigrations as $migrationTime => $migration) {
                $affectedRows    += $this->pdo->fetchAffected($migration['up']);
                $stmt             = 'INSERT INTO __migrations (migration) VALUES (' . $migrationTime . ')';
                $insertMigration += $this->pdo->exec($stmt);
            }

            $this->pdo->commit();
            $this->message  = 'Result migrations:' . \PHP_EOL;
            $this->message .= '  Affected rows #' . $affectedRows . \PHP_EOL;
            $this->message .= '  Applied migrations #' . $insertMigration . \PHP_EOL;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }
    }

    protected function generate(): void
    {
        $time          = \time();
        $this->message = 'Created migration files:' . \PHP_EOL;
        $fileName      = 'migration-' . $time . '-up.sql';
        \touch($this->options['migrations'] . '/' . $fileName);
        $this->message .= '  "' . $fileName . '"' . \PHP_EOL;
        $fileName       = 'migration-' . $time . '-down.sql';
        \touch($this->options['migrations'] . '/' . $fileName);
        $this->message .= '  "' . $fileName . '"' . \PHP_EOL;
    }

    public function __toString(): string
    {
        return $this->message ?? '';
    }
}
