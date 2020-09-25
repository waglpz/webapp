<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

if (! \function_exists('Waglpz\Webapp\sortLongestKeyFirst')) {
    /**
     * @param array<string,mixed> $assocArray
     */
    function sortLongestKeyFirst(array &$assocArray) : void
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
