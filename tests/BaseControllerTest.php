<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use Aidphp\Http\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\BaseController;

final class BaseControllerTest extends TestCase
{
    /** @test */
    public function layoutCanBeChanged() : void
    {
        $view = $this->createMock(PhpRenderer::class);
        $view->expects(self::once())->method('setLayout')->with('new-layout.phtml');
        $baseController = new class ($view) extends BaseController {
            public function __invoke(ServerRequestInterface $request) : ResponseInterface
            {
                return new Response();
            }
        };
        $baseController->setLayout('new-layout');
    }

    /** @test */
    public function suspendLayout() : void
    {
        $view = $this->createMock(PhpRenderer::class);
        $view->expects(self::once())->method('setLayout')->with('');
        $baseController = new class ($view) extends BaseController {
            public function __invoke(ServerRequestInterface $request) : ResponseInterface
            {
                return new Response();
            }
        };
        $baseController->disableLayout();
    }

    /** @test */
    public function jsonResponse() : void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $view    = $this->createMock(PhpRenderer::class);
        $view->expects(self::once())->method('setLayout')->with('');
        $baseController = new class ($view) extends BaseController {
            public function __invoke(ServerRequestInterface $request) : ResponseInterface
            {
                $data = ['a' => 'b', 'c' => ['d' => 'e']];

                return $this->renderJson($data);
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

    /** @test */
    public function renderError() : void
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
                 ]
             )->willReturnArgument(0);
        $baseController = new class ($view) extends BaseController {
            public function __invoke(ServerRequestInterface $request) : ResponseInterface
            {
                return $this->renderError('Error message', 'trace', 500);
            }
        };

        $response = $baseController($request);
        self::assertSame(500, $response->getStatusCode());
    }

    /** @test */
    public function getDataFromJsonRequest() : void
    {
        $view    = $this->createMock(PhpRenderer::class);
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
        $baseController = new class ($view) extends BaseController {
            public function __invoke(ServerRequestInterface $request) : ResponseInterface
            {
                return new Response();
            }

            /** @return array<mixed> */
            public function test(ServerRequestInterface $request) : array
            {
                return $this->dataFromRequest($request);
            }
        };

        $data = $baseController->test($request);
        self::assertEquals(['q' => 'q1', 'p' => 'p1', 'mix' => 'qMix'], $data);
    }

    /** @test */
    public function getDataFromRequest() : void
    {
        $view    = $this->createMock(PhpRenderer::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['q' => 'q1', 'mix' => 'qMix']);
        $request->expects(self::once())->method('getHeaderLine')
                ->with('content-type')
                ->willReturn('multipart/form-data');

        $request->expects(self::once())->method('getParsedBody')->willReturn(['p' => 'p1', 'mix' => 'pMix']);
        $baseController = new class ($view) extends BaseController {
            public function __invoke(ServerRequestInterface $request) : ResponseInterface
            {
                return new Response();
            }

            /** @return array<mixed> */
            public function test(ServerRequestInterface $request) : array
            {
                return $this->dataFromRequest($request);
            }
        };

        $data = $baseController->test($request);
        self::assertEquals(['q' => 'q1', 'p' => 'p1', 'mix' => 'qMix'], $data);
    }

    /** @test */
    public function getDataFromRequestGetOnlyPresent() : void
    {
        $view    = $this->createMock(PhpRenderer::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['a' => 'b']);
        $request->expects(self::never())->method('getHeaderLine');
        $request->expects(self::never())->method('getParsedBody');

        $baseController = new class ($view) extends BaseController {
            public function __invoke(ServerRequestInterface $request) : ResponseInterface
            {
                return new Response();
            }

            /** @return array<mixed> */
            public function test(ServerRequestInterface $request) : array
            {
                return $this->dataFromRequest($request);
            }
        };

        $data = $baseController->test($request);
        self::assertEquals(['a' => 'b'], $data);
    }
}
