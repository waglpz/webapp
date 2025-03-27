<?php

declare(strict_types=1);

use Aura\Sql\ExtendedPdoInterface;
use GeneratedHydrator\Configuration;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Waglpz\Webapp\Common\ValueObjectIdentifier;
use Waglpz\Webapp\Contract\Pagination;

if (! \function_exists('classShortName')) {
    /**
     * @param class-string $className
     *
     * @throws \ReflectionException
     */
    function classShortName(string $className): string
    {
        return (new \ReflectionClass($className))->getShortName();
    }
}

if (! \function_exists('classShortNameLCFirst')) {
    /**
     * @param class-string $className
     *
     * @throws \ReflectionException
     */
    function classShortNameLCFirst(string $className): string
    {
        return \lcfirst(\classShortName($className));
    }
}

if (! \function_exists('createValueObject')) {
    /**
     * @param class-string $valueObjectClassName
     *
     * @throws \ReflectionException
     */
    function createValueObject(string $valueObjectClassName, mixed $value): object|null
    {
        if ($value === null) {
            return null;
        }

        if (\is_string($value)) {
            // here we will apply value if class type is an enumerator class
            if (\enum_exists($valueObjectClassName)) {
                try {
                    if (\method_exists($valueObjectClassName, 'from')) {
                        $enum = $valueObjectClassName::from($value);
                        if ($enum instanceof \BackedEnum) {
                            return $enum;
                        }
                    }

                    $enum = \constant($valueObjectClassName . '::' . $value);

                    if ($enum instanceof \UnitEnum) {
                        return $enum;
                    }
                } catch (\Throwable $exception) {
                    throw new \RuntimeException(
                        'Can not create Enum Instance of Value Object.',
                        $exception->getCode(),
                        $exception,
                    );
                }
            }

            // here we try value as Object UUID Identifier also known primary key in DB
            if (\is_subclass_of($valueObjectClassName, ValueObjectIdentifier::class)) {
                if (Uuid::isValid($value)) {
                    return $valueObjectClassName::fromString($value);
                }

                return $valueObjectClassName::fromBytes($value);
            }
        }

        return new $valueObjectClassName($value);
    }
}

if (! \function_exists('toArray')) {
    /** @return array<mixed> */
    function toArray(object $value, bool $valuesToStringReadable = false): array
    {
        $hydrator = (new Configuration($value::class))->createFactory()->getHydratorClass();

        return \array_map(
            static function ($value) use ($valuesToStringReadable) {
                if ($value === null) {
                    return null;
                }

                if ($value instanceof \DateTimeImmutable) {
                    return $value->format('Y-m-d H:i:s');
                }

                if (\is_object($value)) {
                    if (isset($value->value)) {
                        if ($value->value instanceof UuidInterface) {
                            return $valuesToStringReadable ? (string) $value->value : $value->value->getBytes();
                        }

                        if (\is_scalar($value->value)) {
                            return $value->value;
                        }
                    }

                    if ($value instanceof \UnitEnum) {
                        return $value->name;
                    }

                    if (\method_exists($value, 'value')) {
                        $scalar = $value->value();
                        if (! \is_scalar($scalar)) {
                            throw new \InvalidArgumentException('Can not transform value object to scalar "$value".');
                        }

                        return $scalar;
                    }

                    if (\method_exists($value, '__toString')) {
                        return (string) $value;
                    }

                    throw new \InvalidArgumentException(
                        \sprintf('Can not transform value object "%s".', $value::class),
                    );
                }

                if (! \is_scalar($value)) {
                    throw new \InvalidArgumentException('Can not transform value object to scalar "$value".');
                }

                return $value;
            },
            (new $hydrator())->extract($value),
        );
    }
}

if (! \function_exists('valuesForObjectHydration')) {
    /**
     * @param array<mixed>               $data
     * @param array<\ReflectionProperty> $properties
     *
     * @return array<object|null>
     *
     * @throws \ReflectionException
     */
    function valuesForObjectHydration(array $data, array $properties): array
    {
        $keys   = [];
        $config = \array_map(
        /** @throws \ReflectionException */
            static function (\ReflectionProperty $item) use ($data, &$keys) {
                $value = $data[$item->getName()] ?? null;
                $type  = $item->getType();
                if ($type === null) {
                    throw new \LogicException(
                        \sprintf(
                            'A "%s" Model Property has not a Type defined, unable to parse property type.',
                            $type,
                        ),
                    );
                }

                \assert($type instanceof \ReflectionNamedType);
                /** @phpstan-var class-string $className */
                $className = $type->getName();
                $keys[]    = $item->getName();

                return \createValueObject($className, $value);
            },
            $properties,
        );

        return \array_combine($keys, $config);
    }
}

if (! \function_exists('toObject')) {
    /**
     * @param class-string<T> $modelClass
     * @param mixed[]         $data
     *
     * @return T
     *
     * @throws \ReflectionException
     *
     * @template T of object
     */
    function toObject(string $modelClass, array $data): object
    {
        $hydratorClass = (new Configuration($modelClass))->createFactory()->getHydratorClass();

        $reflection = new \ReflectionClass($modelClass);
        $properties = $reflection->getProperties(
            \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_READONLY | \ReflectionProperty::IS_READONLY,
        );
        $dummy      = $reflection->newInstanceWithoutConstructor();
        $values     = \valuesForObjectHydration($data, $properties);

        return (new $hydratorClass())->hydrate($values, $dummy);
    }
}

if (! \function_exists('toReadonlyObject')) {
    /**
     * @param class-string<T> $modelClass
     * @param mixed[]         $data
     *
     * @return T
     *
     * @throws \ReflectionException
     *
     * @template T of object
     */
    function toReadonlyObject(string $modelClass, array $data): object
    {
        $reflection = new \ReflectionClass($modelClass);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_READONLY);

        $values = \valuesForObjectHydration($data, $properties);

        return new $modelClass(...$values);
    }
}

if (! \function_exists('stringToUnderscoreLC')) {
    function stringToUnderscoreLC(string $string): string
    {
        $fixed = \preg_replace(
            '/((?<=[a-z])[A-Z])|((?<=[A-Z])[A-Z](?=[a-z]))/',
            '_$1$2',
            $string,
        );
        if ($fixed === null && \preg_last_error() !== \PREG_NO_ERROR) {
            throw new \RuntimeException('Regex Error with code: ' . \preg_last_error());
        }

        \assert(\is_string($fixed));

        return \strtolower($fixed);
    }
}

if (! \function_exists('persistModel')) {
    function persistModel(object $model, ExtendedPdoInterface $pdo): void
    {
        try {
            $class = \classShortName($model::class);
            $table = \stringToUnderscoreLC($class);
            $pdo->beginTransaction();
            $array    = \toArray($model);
            $names    = \array_keys($array);
            $callback = static function (string $name) {
                return $name . '=:' . $name;
            };

            $partStmt = \implode(',', \array_map($callback, $names));

            if (\method_exists($model, 'id')) {
                $idValueObject = $model->id();
            }

            if (\property_exists($model, 'id')) {
                $idValueObject = $model->id;
            }

            if (! isset($idValueObject->value)) {
                throw new \InvalidArgumentException(
                    'Model instance without ID as Value Object not supported.',
                );
            }

            $present = (bool) $pdo->fetchValue(
                'SELECT 1 from ' . $table . ' WHERE id = ?',
                [$idValueObject->value->getBytes()],
            );

            if ($present) {
                $statement = \sprintf(
                    'UPDATE %s SET %s WHERE id = :id',
                    $table,
                    $partStmt,
                );
            } else {
                $statement = \sprintf(
                    'INSERT INTO %s SET %s',
                    $table,
                    $partStmt,
                );
            }

            $pdo->fetchAffected($statement, $array);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();

            throw $exception;
        }
    }
}

if (! \function_exists('persistAsNewModel')) {
    function persistAsNewModel(object $model, ExtendedPdoInterface $pdo): void
    {
        $class    = \classShortName($model::class);
        $table    = \stringToUnderscoreLC($class);
        $array    = \toArray($model);
        $names    = \array_keys($array);
        $callback = static function (string $name) {
            return $name . '=:' . $name;
        };

        $partStmt = \implode(',', \array_map($callback, $names));

        $statement = \sprintf(
            'INSERT INTO %s SET %s',
            $table,
            $partStmt,
        );

        $pdo->fetchAffected($statement, $array);
    }
}

if (! \function_exists('paginationHateOs')) {
    function paginationHateOs(string $endpointUri): Pagination
    {
        return new class ($endpointUri) implements Pagination {
            public const int MAX_ITEMS_ALLOWED_PER_PAGE = 100;

            private int $maxItemsPerPage;
            /** @var callable */
            private $dataTotalCounter;
            private int $total;
            private string $baseUrl;

            public function __construct(string $baseUrl)
            {
                $this->baseUrl = $baseUrl;
            }

            /** @inheritDoc */
            public function __invoke(
                ServerRequestInterface $request,
                callable $dataAccessor,
                callable $dataTotalCounter,
                int $maxItemsPerPage = 10,
            ): array {
                $requestQueryParams     = $request->getQueryParams();
                $this->dataTotalCounter = $dataTotalCounter;
                $page                   = (int) ($requestQueryParams['page'] ?? 0);
                $this->maxItemsPerPage  = \min(
                    (int) ($requestQueryParams['limit'] ?? $maxItemsPerPage),
                    self::MAX_ITEMS_ALLOWED_PER_PAGE,
                );

                $offset = $this->maxItemsPerPage * ($page > 0 ? $page - 1 : 0);

                $data = [];

                foreach (($dataAccessor)($offset, $this->maxItemsPerPage) as $item) {
                    $data[] = \toArray($item, true);
                }

                $this->total = $this->totalItems();

                $queryParams      = $request->getQueryParams();
                $first            = $previous = $self = $next = $last = $queryParams;
                $first['page']    = 1;
                $previous['page'] = \max($page - 1, 1);
                $self['page']     = \max($page, 1);
                $next['page']     = \min(\max($page, 1), $this->totalPages());
                $last['page']     = $this->totalPages();

                return [
                    '_links'       => [
                        'first'    => $this->baseUrl . '?' . \http_build_query($first),
                        'previous' => $this->baseUrl . '?' . \http_build_query($previous),
                        'self'     => $this->baseUrl . '?' . \http_build_query($self),
                        'next'     => $this->baseUrl . '?' . \http_build_query($next),
                        'last'     => $this->baseUrl . '?' . \http_build_query($last),
                    ],
                    'totalItems'   => $this->total,
                    'totalPages'   => $this->totalPages(),
                    'itemsPerPage' => $this->maxItemsPerPage,
                    '_embedded'    => $data,
                ];
            }

            public function totalPages(): int
            {
                return \max((int) \ceil($this->total / $this->maxItemsPerPage), 1);
            }

            public function totalItems(): int
            {
                return ($this->dataTotalCounter)();
            }

            public function maxItemsPerPage(): int
            {
                return $this->maxItemsPerPage;
            }
        };
    }
}

if (! \function_exists('getAllowedFormFields')) {
    /**
     * @param array<int|string>    $formFields
     * @param class-string<object> $modelClass
     *
     * @return array<int|string>
     *
     * @throws \ReflectionException
     */
    function getAllowedFormFields(array $formFields, string $modelClass): array
    {
        if ($formFields === []) {
            return [];
        }

        $reflection = new \ReflectionClass($modelClass);
        $properties = $reflection->getProperties(
            \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_READONLY | \ReflectionProperty::IS_PRIVATE,
        );

        $names = \array_flip(
            \array_map(
                static fn (\ReflectionProperty $property): string => $property->getName(),
                $properties,
            ),
        );

        return \array_filter(
            $formFields,
            static fn ($feldName) => isset($names[$feldName]),
            \ARRAY_FILTER_USE_KEY,
        );
    }
}

if (! \function_exists('whereForQuery')) {
    /**
     * @param class-string<object> $modelClass
     *
     * @throws \Exception
     */
    function whereForQuery(ServerRequestInterface $request, string $modelClass, string|null $tableAlias = null): string
    {
        $felder = $request->getQueryParams()['filter'] ?? [];

        $allowedFields = \getAllowedFormFields($felder, $modelClass);

        if ($allowedFields === []) {
            return '';
        }

        $reflection = new \ReflectionClass($modelClass);
        $properties = $reflection->getProperties(
            \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_READONLY | \ReflectionProperty::IS_PRIVATE,
        );

        $uuidProperties = \array_filter(
            $properties,
            static function (\ReflectionProperty $p): bool {
                if ($p->hasType()) {
                    $type = $p->getType();
                    if (! $type instanceof \ReflectionNamedType) {
                        return false;
                    }

                    return \is_subclass_of($type->getName(), ValueObjectIdentifier::class);
                }

                return false;
            },
        );

        foreach ($uuidProperties as $property) {
            $propName = $property->getName();
            if (! isset($allowedFields[$propName])) {
                continue;
            }

            \assert(\is_string($allowedFields[$propName]));
            $allowedFields[$propName] = Uuid::fromString($allowedFields[$propName]);
        }

        $where      = [];
        $tableAlias = $tableAlias !== null ? $tableAlias . '`.`' : '';

        foreach ($allowedFields as $field => $value) {
            if ($value instanceof UuidInterface) {
                $where[] = \sprintf(' `%s%s` = UNHEX(REPLACE("%s", "-","")) ', $tableAlias, $field, $value->toString());
                continue;
            }

            $booleanOrNull = \filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
            if ($booleanOrNull !== null) {
                $where[] = \sprintf(' `%s%s` = "%s" ', $tableAlias, $field, $booleanOrNull ? '1' : '0');

                continue;
            }

            $where[] = \sprintf(' `%s%s` LIKE "%%%s%%" ', $tableAlias, $field, $value);
        }

        return ' WHERE ' . \implode(' AND ', $where);
    }
}

if (! \function_exists('orderByForQuery')) {
    /**
     * @param class-string<object> $modelClass
     *
     * @throws \ReflectionException
     */
    function orderByForQuery(ServerRequestInterface $request, string $modelClass, string|null $default = null): string
    {
        $felder = $request->getQueryParams()['sort'] ?? [];

        $allowedFields = \getAllowedFormFields($felder, $modelClass);

        if ($allowedFields === []) {
            return $default ?? '';
        }

        $orderBy = [];

        foreach ($allowedFields as $feldname => $feldwert) {
            if (\is_string($feldwert) && \strcasecmp('asc', $feldwert) !== 0 && \strcasecmp('desc', $feldwert) !== 0) {
                continue;
            }

            $orderBy[] = $feldname . ' ' . $feldwert;
        }

        return ' order by ' . \implode(' , ', $orderBy) . ' ';
    }
}
