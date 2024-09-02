<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Common\Repository;

use Aura\Sql\ExtendedPdoInterface;
use Waglpz\Webapp\Common\ValueObjectIdentifier;

/**
 * @template TID of ValueObjectIdentifier
 * @template T of object
 */
abstract class Repository
{
    private string $table;

    public function __construct(public readonly ExtendedPdoInterface $pdo)
    {
    }

    /**
     * @param T $model
     *
     * @throws \Throwable
     */
    public function persist(object $model): void
    {
        \persistModel($model, $this->pdo);
    }

    /** @throws \ReflectionException */
    public function table(): string
    {
        if (! isset($this->table)) {
            $modelClass  = $this->modelClass();
            $modelClass  = \classShortName($modelClass);
            $this->table = \stringToUnderscoreLC($modelClass);
        }

        return $this->table;
    }

    /** @return class-string<T> */
    abstract public function modelClass(): string;

    /** @throws \ReflectionException */
    public function __findOne(ValueObjectIdentifier $id): object|null
    {
        $statement   = \sprintf('SELECT * from `%s` where id = ?', $this->table());
        $fetchedData = $this->pdo->fetchOne($statement, [$id->value->getBytes()]);

        if ($fetchedData === false) {
            return null;
        }

        $modelClass = $this->modelClass();

        return \toReadonlyObject($modelClass, $fetchedData);
    }

    /**
     * @param TID $id
     *
     * @throws \ReflectionException
     */
    public function exist(ValueObjectIdentifier $id): bool
    {
        return $this->__findOne($id) !== null;
    }

    /**
     * @param TID $id
     *
     * @return T
     *
     * @throws \ReflectionException
     */
    public function getOne(ValueObjectIdentifier $id): object
    {
        $object     = $this->__findOne($id);
        $modelClass = $this->modelClass();
        if ($object === null) {
            $exceptionClass = $modelClass . 'NichtGefunden';

            throw $exceptionClass::byId($id);
        }

        \assert($object instanceof $modelClass);

        return $object;
    }

    /**
     * @param TID $id
     *
     * @return \Generator<T>
     *
     * @throws \ReflectionException
     */
    public function findById(string|null $sort = null, ValueObjectIdentifier ...$id): \Generator
    {
        if (\count($id) <= 0) {
            return null;
        }

        $statement = \sprintf(
            'SELECT * FROM %s WHERE id IN (:id) %s',
            $this->table(),
            $sort,
        );
        $params    = [
            'id' => \array_map(static fn (ValueObjectIdentifier $id) => $id->value->getBytes(), $id),
        ];

        $class = $this->modelClass();

        foreach ($this->pdo->yieldAll($statement, $params) as $data) {
            /** @phpstan-var array<string,mixed> $data */
            yield \toObject($class, $data);
        }
    }

    /**
     * @return \Generator<T>
     *
     * @throws \ReflectionException
     */
    public function listing(
        int $limit,
        int $page,
        string|null $sort = null,
        string|null $where = null,
    ): \Generator {
        $statement = \sprintf(
            'SELECT SQL_CALC_FOUND_ROWS * FROM `%s` %s %s LIMIT %d, %d',
            $this->table(),
            $where,
            $sort,
            $page,
            $limit,
        );

        $class = $this->modelClass();

        foreach ($this->pdo->yieldAll($statement) as $data) {
            /** @phpstan-var array<string,mixed> $data */
            yield \toReadonlyObject($class, $data);
        }
    }

    public function lastResultCount(): int
    {
        // @phpstan-ignore-next-line
        return (int) $this->pdo->fetchValue('SELECT FOUND_ROWS()');
    }

    /**
     * @param array<string, mixed> $whereClauses
     *
     * @return array<int, array<int, bool|float|int|string>|string>
     */
    public function keyValuesToWhereSqlAndParams(array $whereClauses): array
    {
        $keys           = \array_keys($whereClauses);
        $whereSqlParams = \array_map(static fn (string $key): string => ' ' . $key . ' = ? ', $keys);
        $whereSqlPart   = \implode(' AND ', $whereSqlParams);

        $params = \array_values(
            \array_map(
                static function ($value) {
                    if ($value instanceof \DateTimeImmutable) {
                        return $value->format('Y-m-d H:i:s');
                    }

                    if ($value instanceof ValueObjectIdentifier) {
                        return $value->value->getBytes();
                    }

                    if ($value instanceof \UnitEnum) {
                        return $value->name;
                    }

                    if (\is_object($value)) {
                        if (\method_exists($value, '__toString')) {
                            return (string) $value;
                        }

                        if (\method_exists($value, 'value')) {
                            return $value->value();
                        }
                    }

                    if (\is_scalar($value)) {
                        return $value;
                    }

                    throw new \InvalidArgumentException(
                        'Invalid value to string transformation. got ' . \print_r($value, true),
                    );
                },
                $whereClauses,
            ),
        );

        return [$whereSqlPart, $params];
    }

    /**
     * @param array<string, mixed> $whereClauses
     *
     * @throws \ReflectionException
     */
    public function __findOneBy(array $whereClauses): object|null
    {
        [$whereSqlPart, $params] = $this->keyValuesToWhereSqlAndParams($whereClauses);
        \assert(\is_string($whereSqlPart));
        \assert(\is_array($params));

        $statement   = \sprintf('SELECT * FROM `%s` WHERE %s', $this->table(), $whereSqlPart);
        $fetchedData = $this->pdo->fetchOne($statement, $params);

        if ($fetchedData === false) {
            return null;
        }

        $modelClass = $this->modelClass();

        return \toObject($modelClass, $fetchedData);
    }

    /**
     * @param array<string, mixed> $whereClauses
     *
     * @throws \ReflectionException
     */
    public function existBy(array $whereClauses): bool
    {
        $object = $this->__findOneBy($whereClauses);
        $class  = $this->modelClass();

        if ($object !== null && ! $object instanceof $class) {
            throw new \UnexpectedValueException(
                \sprintf(
                    'Object must of type [%s] got [%s].',
                    $class,
                    static::class,
                ),
            );
        }

        return $object !== null;
    }

    /**
     * @param array<string, mixed> $whereClauses
     *
     * @return T
     *
     * @throws \ReflectionException
     * @throws \UnexpectedValueException
     */
    public function getOneBy(array $whereClauses): object
    {
        $object = $this->__findOneBy($whereClauses);
        $class  = $this->modelClass();

        if ($object === null) {
            throw new \UnexpectedValueException(
                \sprintf('Object must of type [%s] got null.', $class),
            );
        }

        if (! $object instanceof $class) {
            throw new \UnexpectedValueException(
                \sprintf(
                    'Object must of type [%s] got [%s].',
                    $class,
                    $object::class,
                ),
            );
        }

        return $object;
    }

    /** @throws \ReflectionException */
    public function __getOne(ValueObjectIdentifier $id): object
    {
        $object = $this->__findOne($id);
        $class  = $this->modelClass();

        if ($object === null) {
            throw new \Error(
                \sprintf('NOT FOUND Model %s by ID %s.', $class, $id->value->toString()),
            );
        }

        \assert($object instanceof $class);

        return $object;
    }

    /** @throws \ReflectionException */
    public function __deleteById(ValueObjectIdentifier ...$id): int|null
    {
        if (\count($id) <= 0) {
            return null;
        }

        $statement = \sprintf(
            'DELETE FROM %s WHERE id IN (?) LIMIT %d',
            $this->table(),
            \count($id),
        );

        $params = [
            \array_map(static fn ($id) => $id->value->getBytes(), $id),
        ];

        return $this->pdo->fetchAffected($statement, $params);
    }

    /** @throws \ReflectionException */
    public function __fetchById(string|null $sort = null, ValueObjectIdentifier ...$id): \Generator
    {
        if (\count($id) <= 0) {
            return null;
        }

        $statement = \sprintf(
            'SELECT * FROM %s WHERE id IN (?) %s',
            $this->table(),
            $sort,
        );
        $params    = [
            \array_map(static fn ($id) => $id->value->getBytes(), $id),
        ];

        $class = $this->modelClass();

        foreach ($this->pdo->yieldAll($statement, $params) as $data) {
            /** @phpstan-var array<string,mixed> $data */
            yield \toReadonlyObject($class, $data);
        }
    }
}
