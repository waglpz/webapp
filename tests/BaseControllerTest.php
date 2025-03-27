<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\BaseController;

use function Waglpz\Webapp\jsonResponse;

final class BaseControllerTest extends TestCase
{
    /**
     * @throws Exception
     *
     * @test
     */
    public function jsonResponse(): void
    {
        $request        = $this->createMock(ServerRequestInterface::class);
        $baseController = new class extends BaseController {
            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                $data = ['a' => 'b', 'c' => ['d' => 'e']];

                return jsonResponse($data);
            }
        };

        $response = $baseController($request);
        self::assertSame(200, $response->getStatusCode());
        // exactly in this form
        $expectation = '{
    "a": "b",
    "c": {
        "d": "e"
    }
}';
        self::assertEquals($expectation, (string) $response->getBody());
    }
}
