<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Dice\Dice;
use MonologFactory\LoggerFactory;
use Psr\Log\LoggerInterface;

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
                        -\strlen($_SERVER['PHP_SELF'])
                    )
                ),
                '/'
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
                'Could not gatter version from git history'
            );
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
    function logger(array $config, ?string $name = null): LoggerInterface
    {
        static $logger = [];

        $name ??= 'default';

        if (! isset($logger[$name]) && \is_array($config[$name])) {
            $logger[$name] = (new LoggerFactory())->create($name, $config[$name]);
        }

        return $logger[$name];
    }
}

if (! \function_exists('Waglpz\Webapp\config')) {
    function config(?string $partial = null, ?string $projectRoot = null): mixed
    {
        $projectRoot = \Waglpz\Webapp\projectRoot($projectRoot);
        $config      = include $projectRoot . '/main.php';

        if ($partial !== null && ! isset($config[$partial])) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Unknown config key given "%s".',
                    $partial
                )
            );
        }

        return $partial !== null ? $config[$partial] : $config;
    }
}

if (! \function_exists('Waglpz\Webapp\projectRoot')) {
    function projectRoot(?string $projectRoot = null): string
    {
        if ($projectRoot === null) {
            \assert(\defined('PROJECT_CONFIG_DIRECTORY'));

            return \PROJECT_CONFIG_DIRECTORY;
        }

        return $projectRoot;
    }
}

if (! \function_exists('Waglpz\Webapp\container')) {
    function container(): Container
    {
        static $container = null;
        if ($container !== null) {
            return $container;
        }

        if (! \defined('PROJECT_CONFIG_DIRECTORY')) {
            throw new \Error(
                'Runtime Constant "PROJECT_CONFIG_DIRECTORY" may not defined as expected.'
            );
        }

        $dicRules = include \PROJECT_CONFIG_DIRECTORY . '/dic.rules.php';
        $dic      = (new Dice())->addRules($dicRules);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $container = new Container($dic);

        return $container;
    }
}

if (! \function_exists('Waglpz\Webapp\cliExecutorName')) {
    function cliExecutorName(): string
    {
        static $cliExecutor = null;
        if ($cliExecutor === null) {
            $cliExecutor = isset($_SERVER['COMPOSER_HOME'], $_SERVER['COMPOSER_BINARY'])
                ? ' composer waglpz:cli '
                : ' php ' . $_SERVER['argv'][0] . ' ';
        }

        return $cliExecutor;
    }
}
