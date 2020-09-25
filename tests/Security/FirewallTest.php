<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\Security;

use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\Security\Firewall;
use Waglpz\Webapp\Security\Forbidden;
use Waglpz\Webapp\Security\Rollen;

final class FirewallTest extends TestCase
{
    /** @test */
    public function hashesInUriUndRoute(): void
    {
        $uri                        = '/a1b2c3/abc';
        $_SESSION['hash_uri'][$uri] = '/a1b2c3';
        $regeln                     = ['/abc' => ['ROLLE_A']];
        $rollen                     = ['ROLLE_A'];
        $request                    = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getRequestTarget')->willReturn($uri);
        $firewall = new Firewall($regeln, $rollen);

        $firewall->checkRules($request);
    }

    /** @test */
    public function hashesInUriUndRouteForbidden(): void
    {
        $uri                        = '/a1b2c3/abc';
        $_SESSION['hash_uri'][$uri] = '/a1b2c3';
        $regeln                     = ['/abc' => ['ROLLE_A']];
        $rollen                     = ['ROLLE_B'];
        $request                    = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getRequestTarget')->willReturn($uri);
        $firewall = new Firewall($regeln, $rollen);
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('Unberechtigt');
        $firewall->checkRules($request);
    }

    /**
     * @param array<string,array<string>> $regeln
     * @param array<string>               $rollen
     *
     * @dataProvider notAllowed
     * @test
     */
    public function throwsErrorIfRollenNotAllowedForRoute(string $uri, array $regeln, array $rollen): void
    {
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('Unberechtigt');
        $this->expectExceptionCode(403);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getRequestTarget')->willReturn($uri);
        $firewall = new Firewall($regeln, $rollen);

        $firewall->checkRules($request);
    }

    /**
     * @param array<string,array<string>> $regeln
     * @param array<string>               $rollen
     *
     * @dataProvider allowed
     * @test
     */
    public function noErrorIfRollenAllowedForRoute(string $uri, array $regeln, array $rollen): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getRequestTarget')->willReturn($uri);
        $firewall = new Firewall($regeln, $rollen);

        $firewall->checkRules($request);
    }

    /**
     * @return Generator<mixed>
     */
    public function notAllowed(): Generator
    {
        yield ['/a', ['/' => [Rollen::UNBEKANNT]], []];
        yield ['/a', ['/' => ['ROLLE_A']], ['ROLLE_B']];
        yield ['/a', ['/a' => ['ROLLE_A']], ['ROLLE_B']];
        yield ['/a', ['/a' => []], ['ROLLE_B']];
        yield ['/a', ['/a' => ['ROLLE_A']], []];
        yield ['/a', ['/a' => []], []];
        yield ['/a', ['/ab' => []], []];
        yield ['/ab', ['/a' => []], []];

        yield [
            '/a',
            [
                '/'   => ['ROLLE_B'],
                '/a'  => ['ROLLE_A'],
                '/ab' => ['ROLLE_B'],
            ],
            ['ROLLE_B'],
        ];

        yield [
            '/a',
            [
                '/'   => ['ROLLE_B'],
                '/ab' => ['ROLLE_B'],
                '/a'  => ['ROLLE_A'],
            ],
            ['ROLLE_B'],
        ];

        yield [
            '/a',
            [
                '/a'  => ['ROLLE_A'],
                '/'   => ['ROLLE_B'],
                '/ab' => ['ROLLE_B'],
            ],
            ['ROLLE_B'],
        ];

        yield [
            '/a',
            [
                '/a'  => ['ROLLE_A'],
                '/ab' => ['ROLLE_B'],
                '/'   => ['ROLLE_B'],
            ],
            ['ROLLE_B'],
        ];

        yield [
            '/a',
            [
                '/ab' => ['ROLLE_B'],
                '/a'  => ['ROLLE_A'],
                '/'   => ['ROLLE_B'],
            ],
            ['ROLLE_B'],
        ];

        yield [
            '/a',
            [
                '/ab' => ['ROLLE_B'],
                '/'   => ['ROLLE_B'],
                '/a'  => ['ROLLE_A'],
            ],
            ['ROLLE_B'],
        ];
    }

    /**
     * @return Generator<mixed>
     */
    public function allowed(): Generator
    {
        yield ['/a', ['/a' => [Rollen::UNBEKANNT]], [Rollen::UNBEKANNT]];
        yield ['/', ['/' => [Rollen::UNBEKANNT]], []];
        yield ['/', ['/' => [Rollen::UNBEKANNT]], ['ROLLE_A']];
        yield ['/', ['/' => ['ROLLE_A']], ['ROLLE_A']];
        yield ['/', ['/' => ['ROLLE_A', 'ROLLE_B']], ['ROLLE_A']];
        yield ['/', ['/' => ['ROLLE_A', 'ROLLE_B']], ['ROLLE_B']];
        yield ['/', ['/' => ['ROLLE_A', 'ROLLE_B']], ['ROLLE_A', 'ROLLE_B']];

        yield [
            '/a',
            [
                '/'   => ['ROLLE_B'],
                '/a'  => ['ROLLE_A'],
                '/ab' => ['ROLLE_B'],
            ],
            ['ROLLE_A'],
        ];

        yield [
            '/a',
            [
                '/'   => ['ROLLE_B'],
                '/ab' => ['ROLLE_B'],
                '/a'  => ['ROLLE_A'],
            ],
            ['ROLLE_A'],
        ];

        yield [
            '/a',
            [
                '/a'  => ['ROLLE_A'],
                '/'   => ['ROLLE_B'],
                '/ab' => ['ROLLE_B'],
            ],
            ['ROLLE_A'],
        ];

        yield [
            '/a',
            [
                '/a'  => ['ROLLE_A'],
                '/ab' => ['ROLLE_B'],
                '/'   => ['ROLLE_B'],
            ],
            ['ROLLE_A'],
        ];

        yield [
            '/a',
            [
                '/ab' => ['ROLLE_B'],
                '/a'  => ['ROLLE_A'],
                '/'   => ['ROLLE_B'],
            ],
            ['ROLLE_A'],
        ];

        yield [
            '/a',
            [
                '/ab' => ['ROLLE_B'],
                '/'   => ['ROLLE_B'],
                '/a'  => ['ROLLE_A'],
            ],
            ['ROLLE_A'],
        ];
    }
}
