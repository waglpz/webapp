<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Common\Db;

use Aura\Sql\ExtendedPdoInterface;
use Webmozart\Assert\Assert;

final readonly class DbHelperFunctions
{
    public function __construct(private ExtendedPdoInterface $db)
    {
    }

    public function existByIdInTable(string $table, string $idAttribut, string|int $idValue): bool
    {
        $stmt          = \sprintf('SELECT EXISTS (SELECT 1 FROM %s WHERE %s = ?)', $table, $idAttribut);
        $resourceExist = $this->db->fetchValue($stmt, [$idValue]);

        return $resourceExist === 1;
    }

    public function lastInsertId(): int
    {
        $lastInsertId = $this->db->fetchValue('SELECT LAST_INSERT_ID()');
        \Waglpz\Webapp\Common\Assert\Assert::integerish($lastInsertId);

        return (int) $lastInsertId;
    }

    public function limitOffsetClause(int $limit, int $offset): string
    {
        return \sprintf('LIMIT %d OFFSET %d', $limit, $offset);
    }

    public function totalCount(string $table): int
    {
        $count = $this->db->fetchValue('SELECT COUNT(*) FROM ' . $table);
        Assert::integerish($count);

        return (int) $count;
    }

    /**
     * @param array<string,string> $sort
     * @param array<string,string> $filter
     */
    public function addWhereSortLimitOffsetToStatement(
        string $statement,
        array $filter,
        array $sort,
        int $limit,
        int $offset,
    ): string {
        $extendedStatement = $this->extendSqlWithWhere($statement, $filter);

        $extendedStatement = $this->extendSqlWithOrder($extendedStatement, $sort);

        return $this->extendSqlWithLimitOffset($extendedStatement, $limit, $offset);
    }

    public function extendSqlWithLimitOffset(string $statement, int $limit, int $offset): string
    {
        if (\stripos($statement, 'LIMIT') !== false) {
            $message = 'SQL $stmt contains unexpected clauses "%s". Please remove them before add a ORDER BY clause.';

            throw new \InvalidArgumentException($message);
        }

        $statementLimitOffset = $this->limitOffsetClause($limit, $offset);

        return \sprintf('%s %s', $statement, $statementLimitOffset);
    }

    /** @param array<string,string> $filter */
    public function extendSqlWithWhere(string $stmt, array $filter): string
    {
        if (
            \stripos($stmt, 'GROUP BY') !== false
            || \stripos($stmt, 'HAVING') !== false
            || \stripos($stmt, 'ORDER BY') !== false
            || \stripos($stmt, 'LIMIT') !== false
            || \stripos($stmt, 'OFFSET') !== false
        ) {
            $message = 'SQL $stmt contains unexpect clauses "%s". Please remove them before add a WHERE clause.';

            throw new \InvalidArgumentException($message);
        }

        if ($filter === []) {
            return $stmt;
        }

        $filterNames = \array_keys($filter);

        $where = \array_reduce(
            $filterNames,
            static function ($accumulator, $item): string {
                $likePart = $item . ' LIKE :' . $item;

                return $accumulator . ($accumulator === 'WHERE ' ? $likePart : ' AND ' . $likePart);
            },
            'WHERE ',
        );

        return \sprintf('%s %s', $stmt, $where);
    }

    /** @param array<string,string> $sort */
    public function extendSqlWithOrder(string $stmt, array $sort): string
    {
        if (\stripos($stmt, 'LIMIT') !== false) {
            $message = 'SQL $stmt contains unexpected clauses "%s". Please remove them before add a ORDER BY clause.';

            throw new \InvalidArgumentException($message);
        }

        if ($sort === []) {
            return $stmt;
        }

        $sortNames = \array_keys($sort);

        $orderByAddendum = \array_reduce(
            $sortNames,
            static function ($accumulator, $item) use ($sort): string {
                if (\stripos($sort[$item], 'DESC') === false && \stripos($sort[$item], 'ASC') === false) {
                    $message = 'Sort with unexpected value: ' . $sort[$item] . '. Allowed values are ASC|DESC.';

                    throw new \InvalidArgumentException($message);
                }

                $orderByPart = $item . ' ' . \strtoupper($sort[$item]);

                return $accumulator . ($accumulator === ' ORDER BY ' ? $orderByPart : ' , ' . $orderByPart);
            },
            ' ORDER BY ',
        );

        return \sprintf('%s %s', $stmt, $orderByAddendum);
    }

    /**
     * @param array<string,string> $filter
     * @param array<string,string> $sort
     *
     * @return array<string,string>
     */
    public function filterAndSortParametersForPdoBinding(array $filter, array $sort): array
    {
        $params  = \array_map(static function ($item) {
            return '%' . $item . '%';
        }, $filter);
        $params += $sort;

        return $params;
    }

    /** @param array<int,string> $params */
    public function createInsertSql(string $table, array $params): string
    {
        $attributes = \sprintf('%s', \implode(', ', $params));
        $values     = \sprintf('%s', ':' . \implode(', :', $params));

        return \sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, $attributes, $values);
    }

    /** @param array<string, bool|float|int|string|null> $params */
    public function createUpdateSql(string $table, string $idAttribut, array $params): string
    {
        $setClause = \implode(', ', \array_map(static fn ($param) => $param . '= :' . $param, \array_keys($params)));

        return \sprintf('UPDATE %1$s SET %2$s WHERE %3$s = :%3$s', $table, $setClause, $idAttribut);
    }
}
