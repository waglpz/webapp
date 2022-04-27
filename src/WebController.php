<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Aidphp\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;

abstract class WebController extends BaseController
{
    private PhpRenderer $view;

    public function __construct(PhpRenderer $view)
    {
        $this->view = $view;
    }

    abstract public function __invoke(ServerRequestInterface $request): ResponseInterface;

    public function setLayout(string $layout): void
    {
        $this->view->setLayout($layout . '.phtml');
    }

    /** @param array<mixed> $data */
    public function render(
        array $data = [],
        int $httpResponseStatus = 200,
        ?string $template = null
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

    protected function renderJson(?array $data, int $httpResponseStatus = 200): ResponseInterface
    {
        $this->disableLayout();

        return parent::renderJson($data, $httpResponseStatus);
    }

    public function disableLayout(): void
    {
        $this->view->setLayout('');
    }

    public function renderError(string $message, ?string $trace, int $code): ResponseInterface
    {
        $response = new Response($code);
        $this->disableLayout();
        $response = $this->view->render(
            $response,
            'errorAction.phtml',
            [
                'message' => $message,
                'trace'   => \APP_ENV === 'dev' ? $trace : 'Error Trace ist ausgeschaltet.',
            ]
        );

        return $response;
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
