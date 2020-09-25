<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

if (! \function_exists('Waglpz\Webapp\webBase')) {
    function webBase(): string
    {
        static $base;
        if (isset($base)) {
            return $base;
        }

        if (\PHP_SAPI !== 'cli') {
            $base = \rtrim(\dirname(\substr($_SERVER['SCRIPT_FILENAME'], -\strlen($_SERVER['PHP_SELF']))), '/');
        } else {
            $base = '';
        }

        return $base;
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
            throw new \RuntimeException('Could not gatter version from git history');
        }

        return \trim($version);
    }
}

if (! \function_exists('Waglpz\Webapp\sortLongestKeyFirst')) {
    /**
     * @param array<string,mixed> $assocArray
     */
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
            }
        );
    }
}

if (! \function_exists('Waglpz\Webapp\logger')) {
    /** @param array<mixed> $config */
    function logger(array $config): \Psr\Log\LoggerInterface
    {
        static $logger;
        if (! isset($logger)) {
            $logger = (new \MonologFactory\LoggerFactory())->create('default', $config['default']);
        }

        return $logger;
    }
}
