<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Contract;

use Psr\Http\Message\ServerRequestInterface;

interface Pagination
{
    /** @return array<string,mixed> */
    public function __invoke(
        ServerRequestInterface $request,
        callable $dataAccessor,
        callable $dataTotalCounter,
        int $maxItemsPerPage = 10,
    ): array;

    public function totalPages(): int;

    public function maxItemsPerPage(): int;

    public function totalItems(): int;
}
