<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use Aidphp\Http\Response;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\BaseController;
use Waglpz\Webapp\WebController;

use function Waglpz\Webapp\dataFromRequest;
use function Waglpz\Webapp\jsonResponse;

final class WebControllerTest extends TestCase
{
    /**
     * @throws Exception
     *
     * @test
     */
    public function layoutCanBeChanged(): void
    {
        $view = $this->createMock(PhpRenderer::class);
        $view->expects(self::once())->method('setLayout')->with('new-layout.phtml');
        $baseController = new class ($view) extends WebController {
            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
        $baseController->setLayout('new-layout');
    }

    /**
     * @throws Exception
     *
     * @test
     */
    public function suspendLayout(): void
    {
        $view = $this->createMock(PhpRenderer::class);
        $view->expects(self::once())->method('setLayout')->with('');
        $baseController = new class ($view) extends WebController {
            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
        $baseController->disableLayout();
    }

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
    public function renderError(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $view    = $this->createMock(PhpRenderer::class);
        $view->expects(self::once())->method('setLayout')->with('');
        $view->expects(self::once())
             ->method('render')
             ->with(
                 self::isInstanceOf(ResponseInterface::class),
                 'errorAction.phtml',
                 [
                     'message' => 'Error message',
                     'trace'   => 'Error Trace ist ausgeschaltet.',
                 ],
             )->willReturnArgument(0);
        $baseController = new class ($view) extends WebController {
            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                return $this->renderError('Error message', 'trace', 500);
            }
        };

        $response = $baseController($request);
        self::assertSame(500, $response->getStatusCode());
    }

    /**
     * @throws Exception
     * @throws \JsonException
     *
     * @test
     */
    public function getDataFromJsonRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['q' => 'q1', 'mix' => 'qMix']);
        $request->expects(self::once())->method('getHeaderLine')
                ->with('content-type')
                ->willReturn('application/json');
        $streamContent = $this->createMock(StreamInterface::class);
        $jsonPayload   = \json_encode(['p' => 'p1', 'mix' => 'pMix'], \JSON_THROW_ON_ERROR);
        $streamContent->expects(self::once())->method('getContents')->willReturn($jsonPayload);
        $request->expects(self::once())->method('getBody')->willReturn($streamContent);
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
     * @throws \JsonException
     *
     * @test
     */
    public function getDataFromJsonUtf8Request(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::once())->method('getQueryParams')->willReturn([]);
        $request->expects(self::once())->method('getHeaderLine')
                ->with('content-type')
                ->willReturn('application/json; charset=utf-8');
        $streamContent = $this->createMock(StreamInterface::class);
        $jsonPayload   = \json_encode(['p' => 'p1'], \JSON_THROW_ON_ERROR);
        $streamContent->expects(self::once())->method('getContents')->willReturn($jsonPayload);
        $request->expects(self::once())->method('getBody')->willReturn($streamContent);
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
        self::assertEquals(['p' => 'p1'], $data);
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
    public function getDataFromRequestGetMethodOnlyPresent(): void
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
