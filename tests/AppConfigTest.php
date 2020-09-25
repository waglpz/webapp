<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use PHPUnit\Framework\TestCase;
use Waglpz\Webapp\App;
use Waglpz\Webapp\ExceptionHandler;

final class AppConfigTest extends TestCase
{
    /** @var array<mixed> */
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = [
            'router'            => static fn () => null,
            'view'              => [
                'view_helper_factory' => \stdClass::class,
                'layout'              => '',
                'templates'           => '',
                'attributes'          => [],
            ],
            'viewHelpers'       => [],
            'exception_handler' => new ExceptionHandler(),
        ];
    }

    /** @test */
    public function fehlerBeimHolenConfigVorDemDasAppInstanziiertWar(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Application config is empty, maybe Application wasn\'t properly instantiated.'
        );
        App::getConfig();
    }

    /** @test */
    public function fehlerBeimHolenNichtExistentPartialConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown config key given "key".');
        (new App($this->config));
        App::getConfig('key');
    }

    /** @test */
    public function holenPartialConfig(): void
    {
        $config        = $this->config;
        $config['key'] = 'value';
        (new App($config));
        $configByKey = App::getConfig('key');
        self::assertSame('value', $configByKey);
    }

    /** @test */
    public function holenConfig(): void
    {
        (new App($this->config));
        $factConfig = App::getConfig();
        self::assertSame($this->config, $factConfig);
    }
}
