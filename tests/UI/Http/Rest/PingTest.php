<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\UI\Http\Rest;

use Waglpz\Webapp\Tests\UI\RestTestCase;

final class PingTest extends RestTestCase
{
    /** @test */
    public function pingDerGesundheit(): void
    {
        $uri      = '/api/ping';
        $response = $this->restGetResponse($uri);
        self::assertSame(200, $response->getStatusCode());
        $json = (string) $response->getBody();
        $data = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));
        self::assertArrayHasKey('time', $data);
        self::assertArrayHasKey('apiVersion', $data);
    }
}
