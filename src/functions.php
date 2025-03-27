<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Aidphp\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

if (! \function_exists('Waglpz\Webapp\isSubset')) {
    /**
     * @param array<mixed>      $subset
     * @param array<int,string> $set
     *
     * @return array<mixed>
     */
    function isSubset(
        array $subset,
        array $set,
    ): array {
        $unexpectedValues = [];
        $subset           = \array_keys($subset);
        $set              = \array_flip($set);

        foreach ($subset as $item) {
            if (isset($set[$item])) {
                continue;
            }

            $unexpectedValues[] = $item;
        }

        return $unexpectedValues;
    }
}

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
            $parsedBodyData = (array) $request->getParsedBody();
            $postData       = [];

            if (
                $parsedBodyData === []
                && \str_starts_with($request->getHeaderLine('content-type'), 'application/json')
            ) {
                $stream = $request->getBody();
                $stream->rewind();
                $content = $stream->getContents();

                if ($content !== '') {
                    $postData = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
                }
            }

            if (! \is_array($postData)) {
                $postData = [];
            }

            return \array_replace_recursive($postData, $parsedBodyData, $getData);
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

if (! \function_exists('Waglpz\Webapp\getTraceDigest')) {
    /** @return array<int,string> */
    function getTraceDigest(\Throwable $exception): array
    {
        $trace            = $exception->getTrace();
        $formattedTrace   = [];
        $prefix           = \uniqid() . ' ' . \str_pad('|', \count($trace) + 1, '-');
        $formattedTrace[] = $prefix . ' Exception: ' . $exception::class;
        $formattedTrace[] = $prefix . ' Message: ' . $exception->getMessage();
        foreach ($trace as $item) {
            $prefix = \substr($prefix, 0, -1);
            if (isset($item['file'])) {
                $line             = isset($item['line']) ? ':' . $item['line'] : '';
                $formattedTrace[] = $prefix
                    . ' File: '
                    . $item['file']
                    . $line;
            }

            $args = '';
            if (isset($item['args'])) {
                $args = \implode(
                    ', ',
                    \array_map(
                        static function ($arg) {
                            if (\is_scalar($arg)) {
                                return \preg_replace('/\s+/', ' ', (string) $arg);
                            }

                            if (\is_object($arg)) {
                                return $arg::class;
                            }

                            if (\is_array($arg)) {
                                if ($arg === []) {
                                    return '[]';
                                }

                                if (isset($arg[0]) && $arg[0] instanceof \Closure) {
                                    return $arg[0]::class;
                                }

                                return \preg_replace('/\s+/', ' ', \var_export($arg, true));
                            }
                        },
                        $item['args'],
                    ),
                );
            }

            if (isset($item['class'])) {
                $type          = isset($item['type']) ? ':' . $item['type'] : ' ';
                $formattedItem = $item['class'] . $type . $item['function'] . '(' . $args . ')';
            } else {
                $formattedItem = $item['function'] . '(' . $args . ')';
            }

            $formattedTrace[] = $prefix . ' Function: ' . $formattedItem;
        }

        return $formattedTrace;
    }
}
