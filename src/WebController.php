<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Aidphp\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;

abstract class WebController extends BaseController
{
    public function __construct(private readonly PhpRenderer $view)
    {
    }

    abstract public function __invoke(ServerRequestInterface $request): ResponseInterface;

    public function setLayout(string $layout): void
    {
        $this->view->setLayout($layout . '.phtml');
    }

    /**
     * @param array<mixed> $data
     *
     * @throws \Throwable
     */
    public function render(
        array $data = [],
        int $httpResponseStatus = 200,
        string|null $template = null,
    ): ResponseInterface {
        $classNameAsArray = \explode('\\', static::class);
        $className        = \end($classNameAsArray);
        $template         = ($template ?? \lcfirst($className)) . '.phtml';
        $response         = new Response($httpResponseStatus);

        if (! isset($data['seitenTitle'])) {
            $data['seitenTitle'] = \ucfirst($className);
        }

        return $this->view->render($response, $template, $data);
    }

    /**
     * @param array<mixed> $data
     *
     * @throws \JsonException
     */
    protected function renderJson(array|null $data, int $httpResponseStatus = 200): ResponseInterface
    {
        $this->disableLayout();

        return \Waglpz\Webapp\jsonResponse($data, $httpResponseStatus);
    }

    public function disableLayout(): void
    {
        $this->view->setLayout('');
    }

    /** @throws \Throwable */
    public function renderError(string $message, string|null $trace, int $code): ResponseInterface
    {
        $response = new Response($code);
        $this->disableLayout();

        return $this->view->render(
            $response,
            'errorAction.phtml',
            [
                'message' => $message,
                'trace'   => \APP_ENV === 'dev' ? $trace : 'Error Trace ist ausgeschaltet.',
            ],
        );
    }

    /** @codeCoverageIgnore */
    public static function refresh(ServerRequestInterface $request, bool $terminate = true, string $anchor = ''): void
    {
        $uri = $request->getRequestTarget() . $anchor;
        self::redirect($uri, $terminate);
    }

    /** @codeCoverageIgnore */
    public static function redirect(string $uri, bool $terminate = true, int $statusCode = 302): void
    {
        \header('Location: ' . $uri);
        \http_response_code($statusCode);
        if ($terminate) {
            exit;
        }
    }
}
