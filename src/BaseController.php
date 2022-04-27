<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Aidphp\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class BaseController
{
    abstract public function __invoke(ServerRequestInterface $request): ResponseInterface;

    /** @return array<mixed> */
    protected function dataFromRequest(ServerRequestInterface $request): array
    {
        $getData = $request->getQueryParams();
        if ($request->getMethod() !== 'GET') {
            if (\strpos($request->getHeaderLine('content-type'), 'application/json') === 0) {
                $content  = $request->getBody()->getContents();
                $postData = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
            } else {
                $postData = $request->getParsedBody();
            }

            if (\is_array($postData)) {
                return \array_replace_recursive(
                    $postData,
                    $getData
                );
            }
        }

        return $getData;
    }

    /**
     * @param ?array<mixed> $data
     *
     * @throws \JsonException
     */
    protected function renderJson(?array $data, int $httpResponseStatus = 200): ResponseInterface
    {
        $jsonString = \json_encode(
            $data,
            \JSON_PRETTY_PRINT | \JSON_ERROR_INVALID_PROPERTY_NAME | \JSON_THROW_ON_ERROR
        );

        $response = (new Response($httpResponseStatus))->withHeader('content-type', 'application/json');
        $response->getBody()->write($jsonString);

        return $response;
    }
}
