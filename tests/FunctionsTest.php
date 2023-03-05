<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

use function Waglpz\Webapp\dataFromRequest;
use function Waglpz\Webapp\jsonResponse;
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

    /**
     * @throws \JsonException
     *
     * @test
     */
    public function jsonResponse(): void
    {
        $model    = ['a' => 'b'];
        $response = jsonResponse($model);
        self::assertSame(200, $response->getStatusCode());
        // exactly in this form
        $expectation = '{
    "a": "b"
}';
        self::assertEquals($expectation, (string) $response->getBody());
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws Exception
     *
     * @test
     */
    public function getDataFromRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['q' => 'q1', 'mix' => 'qMix']);
        $request->expects(self::once())->method('getHeaderLine')
                ->with('content-type')
                ->willReturn('multipart/form-data');
        $request->expects(self::once())->method('getParsedBody')->willReturn(['p' => 'p1', 'mix' => 'pMix']);

        $data = dataFromRequest($request);

        self::assertEquals(['q' => 'q1', 'p' => 'p1', 'mix' => 'qMix'], $data);
    }
}
