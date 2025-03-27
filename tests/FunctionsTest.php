<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use Aidphp\Http\ServerRequest;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

use function Waglpz\Webapp\dataFromRequest;
use function Waglpz\Webapp\jsonResponse;
use function Waglpz\Webapp\sortLongestKeyFirst;

final class FunctionsTest extends TestCase
{
    /** @test */
    public function sort(): void
    {
        $array = [
            'a'       => true,
            'aaaa'    => true,
            'aa'      => true,
            'aaaaaa'  => true,
            'aaa'     => true,
            'aaaaaaa' => true,
            'aaaaa'   => true,
        ];

        sortLongestKeyFirst($array);

        $expected = [
            'aaaaaaa' => true,
            'aaaaaa'  => true,
            'aaaaa'   => true,
            'aaaa'    => true,
            'aaa'     => true,
            'aa'      => true,
            'a'       => true,
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
    public function getDataFromRequestForGetMethod(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['p' => 'p1', 'mix' => 'pMix']);
        $request->expects(self::never())->method('getParsedBody');
        $request->expects(self::never())->method('getHeaderLine');
        $request->expects(self::never())->method('getBody');
        $data = dataFromRequest($request);
        self::assertEquals(['p' => 'p1', 'mix' => 'pMix'], $data);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws Exception
     *
     * @test
     */
    public function getDataFromRequestForPostMethodParsedBodyIsEmptyAndJsonPayloadIsEmpty(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['p' => 'p1', 'mix' => 'pMix']);
        $request->expects(self::once())->method('getParsedBody')->willReturn([]);
        $request->expects(self::once())->method('getHeaderLine')
                ->with('content-type')
                ->willReturn('application/json');
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('getContents')->willReturn('');
        $request->expects(self::once())->method('getBody')->willReturn($stream);
        $data = dataFromRequest($request);
        self::assertEquals(['p' => 'p1', 'mix' => 'pMix'], $data);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws Exception
     *
     * @test
     */
    public function getDataFromRequestForPostMethodParsedBodyIsEmptyAndJsonPayloadIsNotEmpty(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['p' => 'p1', 'mix' => 'pMix']);
        $request->expects(self::once())->method('getParsedBody')->willReturn([]);
        $request->expects(self::once())->method('getHeaderLine')
                ->with('content-type')
                ->willReturn('application/json');
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('getContents')->willReturn('{"a":"b"}');
        $request->expects(self::once())->method('getBody')->willReturn($stream);
        $data = dataFromRequest($request);
        self::assertEquals(['p' => 'p1', 'mix' => 'pMix', 'a' => 'b'], $data);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws Exception
     *
     * @test
     */
    public function getDataFromRequestSkipJsonPayloadWhenParsedBodyNotEmpty(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::never())->method('getBody');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['q' => 'q1', 'mix' => 'qMix']);
        $request->expects(self::never())->method('getHeaderLine');
        $request->expects(self::once())->method('getParsedBody')->willReturn(['p' => 'p1', 'mix' => 'pMix']);

        $data = dataFromRequest($request);

        self::assertEquals(['q' => 'q1', 'p' => 'p1', 'mix' => 'qMix'], $data);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws Exception
     *
     * @test
     */
    public function getDataFromRequestWithCustomParsedBody(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('rewind');
        $stream->expects(self::once())->method('getContents')->willReturn('{"a":"b"}');
        $headers = ['content-type' => 'application/json'];
        $request = new ServerRequest('POST', '/', $headers, $stream);

        $data1 = dataFromRequest($request);
        self::assertEquals(['a' => 'b'], $data1);

        $newRequest = $request->withParsedBody(['c' => 'd']);
        $data2      = dataFromRequest($newRequest);
        self::assertEquals(['c' => 'd'], $data2);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     *
     * @test
     */
    public function getDataFromRequestHandlesInvalidJsonGracefully(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::once())->method('getQueryParams')->willReturn(['q' => 'q1']);
        $request->expects(self::once())->method('getParsedBody')->willReturn([]);
        $request->expects(self::once())->method('getHeaderLine')->with('content-type')
                                                                ->willReturn('application/json');

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('getContents')->willReturn('{"a": b}');
        $request->expects(self::once())->method('getBody')->willReturn($stream);

        $this->expectException(\JsonException::class);

        dataFromRequest($request);
    }
}
