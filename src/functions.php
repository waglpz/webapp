<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Aidphp\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

if (! \function_exists('Waglpz\Webapp\jsonResponse')) {
    /**
     * @param array<mixed> $data
     *
     * @throws \JsonException
     */
    function jsonResponse(array|null $data, int $httpResponseStatus = 200): ResponseInterface
    {
        $jsonString = \json_encode(
            $data,
            \JSON_PRETTY_PRINT | \JSON_ERROR_INVALID_PROPERTY_NAME | \JSON_THROW_ON_ERROR,
        );

        $response = (new Response($httpResponseStatus))->withHeader('content-type', 'application/json');
        $response->getBody()->write($jsonString);

        return $response;
    }
}

if (! \function_exists('Waglpz\Webapp\dataFromRequest')) {
    /**
     * @return array<mixed>
     *
     * @throws \JsonException
     */
    function dataFromRequest(ServerRequestInterface $request): array
    {
        $getData = $request->getQueryParams();
        if ($request->getMethod() !== 'GET') {
            if (\str_starts_with($request->getHeaderLine('content-type'), 'application/json')) {
                $content  = $request->getBody()->getContents();
                $postData = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
            } else {
                $postData = $request->getParsedBody();
            }

            if (\is_array($postData)) {
                return \array_replace_recursive(
                    $postData,
                    $getData,
                );
            }
        }

        return $getData;
    }
}

if (! \function_exists('Waglpz\Webapp\webBase')) {
    function webBase(): string
    {
        static $base;
        if (isset($base)) {
            return $base;
        }

        if (\PHP_SAPI !== 'cli') {
            $base = \rtrim(
                \dirname(
                    \substr(
                        $_SERVER['SCRIPT_FILENAME'],
                        -\strlen($_SERVER['PHP_SELF']),
                    ),
                ),
                '/',
            );
        } else {
            $base = '';
        }

        return $base;
    }
}

if (! \function_exists('Waglpz\Webapp\releasedAt')) {
    function releasedAt(): string
    {
        $lastTagInfo = \exec('git describe --always');

        if (\APP_ENV !== 'prod') {
            $lastCommitInfo = \exec('git log --date=short --pretty="%h %ad %s" | head -1');
        } else {
            // HEAD date 2022-02-12
            $lastCommitInfo = \exec('git log --date=short --pretty="%ad" | head -1');
        }

        // $lastTagInfo eg: v1.1.3-14-g9b3745b explain: Tag name-amount of commits after tag-tag hash
        return $lastTagInfo . \PHP_EOL . '(' . $lastCommitInfo . ')';
    }
}

if (! \function_exists('Waglpz\Webapp\version')) {
    function version(): string
    {
        if (\APP_ENV !== 'prod') {
            return \uniqid('dev');
        }

        static $version;
        if (isset($version)) {
            return $version;
        }

        $version = \exec('git describe --always');
        /** @phpstan-var string|bool $version */
        if (! \is_string($version)) {
            throw new \RuntimeException(
                'Could not gatter version from git history',
            );
        }

        return \trim($version);
    }
}

if (! \function_exists('Waglpz\Webapp\sortLongestKeyFirst')) {
    /** @param array<string,mixed> $assocArray */
    function sortLongestKeyFirst(array &$assocArray): void
    {
        \uksort(
            $assocArray,
            static function ($a, $b) {
                if (\strlen($a) > \strlen($b)) {
                    return -1;
                }

                if (\strlen($a) < \strlen($b)) {
                    return 1;
                }

                return 0;
            },
        );
    }
}
