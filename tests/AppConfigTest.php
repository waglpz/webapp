<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use PHPUnit\Framework\TestCase;

use function Waglpz\Webapp\config;

final class AppConfigTest extends TestCase
{
    /** @test */
    public function fehlerBeimHolenNichtExistentPartialConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown config key given "WRONG".');
        config('WRONG', __DIR__ . '/Stubs/config');
    }

    /** @test */
    public function holenPartialConfig(): void
    {
        $configByKey = config('key', __DIR__ . '/Stubs/config');
        self::assertSame('value', $configByKey);
    }

    /** @test */
    public function holenConfig(): void
    {
        $factConfig = config(null, __DIR__ . '/Stubs/config');
        self::assertSame(['key' => 'value'], $factConfig);
    }
}
