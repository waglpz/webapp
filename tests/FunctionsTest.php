<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use PHPUnit\Framework\TestCase;

use function Waglpz\Webapp\sortLongestKeyFirst;

final class FunctionsTest extends TestCase
{
    /** @test */
    public function sort(): void
    {
        $array = [
            'a'   => true,
            'aaaa'  => true,
            'aa'  => true,
            'aaaaaa'  => true,
            'aaa'  => true,
            'aaaaaaa'  => true,
            'aaaaa' => true,
        ];

        sortLongestKeyFirst($array);

        $expected = [
            'aaaaaaa' => true,
            'aaaaaa' => true,
            'aaaaa' => true,
            'aaaa' => true,
            'aaa' => true,
            'aa'  => true,
            'a'   => true,
        ];
        self::assertSame($expected, $array);
    }
}
