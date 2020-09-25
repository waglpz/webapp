<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use PHPUnit\Framework\TestCase;
use function Waglpz\Webapp\sortLongestKeyFirst;

final class FunctionsTest extends TestCase
{
    /** @test */
    public function sort() : void
    {
        $array = [
            'a'   => true,
            'aa'  => true,
            'ab'  => true,
            'aaa' => true,
        ];

        sortLongestKeyFirst($array);

        $expected = [
            'aaa' => true,
            'ab'  => true,
            'aa'  => true,
            'a'   => true,
        ];
        self::assertSame($expected, $array);
    }
}
