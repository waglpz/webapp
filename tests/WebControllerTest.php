<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use Aidphp\Http\Response;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\BaseController;
use Waglpz\Webapp\WebController;

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
}
