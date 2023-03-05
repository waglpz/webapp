<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use Aidphp\Http\Response;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\BaseController;

use function Waglpz\Webapp\dataFromRequest;
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

    /**
     * @throws Exception
     *
     * @test
     */
    public function getDataFromRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['q' => 'q1', 'mix' => 'qMix']);
        $request->expects(self::once())->method('getHeaderLine')
                ->with('content-type')
                ->willReturn('multipart/form-data');

        $request->expects(self::once())->method('getParsedBody')->willReturn(['p' => 'p1', 'mix' => 'pMix']);
        $baseController = new class extends BaseController {
            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }

            /** @return array<mixed> */
            public function test(ServerRequestInterface $request): array
            {
                return dataFromRequest($request);
            }
        };

        $data = $baseController->test($request);
        self::assertEquals(['q' => 'q1', 'p' => 'p1', 'mix' => 'qMix'], $data);
    }

    /**
     * @throws Exception
     *
     * @test
     */
    public function getDataFromRequestIfGetMethodOnlyPresent(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['a' => 'b']);
        $request->expects(self::never())->method('getHeaderLine');
        $request->expects(self::never())->method('getParsedBody');

        $baseController = new class extends BaseController {
            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }

            /** @return array<mixed> */
            public function test(ServerRequestInterface $request): array
            {
                return dataFromRequest($request);
            }
        };

        $data = $baseController->test($request);
        self::assertEquals(['a' => 'b'], $data);
    }
}
