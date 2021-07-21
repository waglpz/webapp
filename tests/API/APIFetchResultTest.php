<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\API;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Waglpz\Webapp\API\APIFetchResult;

final class APIFetchResultTest extends TestCase
{
    /** @test */
    public function hasAPrivateConstructMethod(): void
    {
        $reflection = new \ReflectionClass(APIFetchResult::class);
        self::assertTrue($reflection->hasMethod('__construct'));
        $constructorMethodReflection = $reflection->getConstructor();
        self::assertNotNull($constructorMethodReflection);
        self::assertTrue($constructorMethodReflection->isPrivate());
    }

    /**
     * @throws \Exception
     *
     * @test
     */
    public function okResponseWithDataPayload(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $body     = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('rewind');
        $body->expects(self::once())->method('getSize')->willReturn(1024);
        $body->expects(self::once())->method('getContents')
             ->willReturn('{"a":"A", "b":"B"}');

        $responseOkStatusCode = \random_int(200, 299);
        $response->expects(self::once())->method('getStatusCode')->willReturn($responseOkStatusCode);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $apiResult = APIFetchResult::fromResponse($response);

        self::assertSame('OK', $apiResult->status());
        self::assertTrue($apiResult->ok());
        self::assertFalse($apiResult->serverError());
        self::assertFalse($apiResult->clientError());
        self::assertSame(['a' => 'A', 'b' => 'B'], $apiResult->data());

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method should not called in contexts where status not corresponding to a Api problem.'
        );

        $apiResult->apiProblem();
    }

    /**
     * @throws \Exception
     *
     * @test
     */
    public function apiProblemClientError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $body     = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('rewind');
        $body->expects(self::once())->method('getSize')->willReturn(1024);
        $responseOkStatusCode = \random_int(400, 499);
        $body->expects(self::once())->method('getContents')
             ->willReturn('{"type":"/test", "status": ' . $responseOkStatusCode . ', "detail": "wrong data"}');

        $response->expects(self::once())->method('getStatusCode')->willReturn($responseOkStatusCode);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $apiResult = APIFetchResult::fromResponse($response);

        self::assertSame('CE', $apiResult->status());
        self::assertFalse($apiResult->ok());
        self::assertTrue($apiResult->clientError());
        self::assertFalse($apiResult->serverError());
        self::assertSame([], $apiResult->data());

        $apiProblem = $apiResult->apiProblem()->toArray();
        self::assertSame(
            [
                'type'   => '/test',
                'title'  => '',
                'status' => $responseOkStatusCode,
                'detail' => 'wrong data',
            ],
            $apiProblem
        );
    }

    /**
     * @throws \Exception
     *
     * @test
     */
    public function apiProblemServerError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $body     = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getSize')->willReturn(0);
        $responseOkStatusCode = \random_int(500, 599);
        $body->expects(self::never())->method('getContents');

        $response->expects(self::once())->method('getStatusCode')->willReturn($responseOkStatusCode);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $apiResult = APIFetchResult::fromResponse($response);

        self::assertSame('SE', $apiResult->status());
        self::assertTrue($apiResult->serverError());
        self::assertFalse($apiResult->ok());
        self::assertFalse($apiResult->clientError());
        self::assertSame([], $apiResult->data());

        $apiProblem = $apiResult->apiProblem()->toArray();
        self::assertSame(
            [
                'type'   => '',
                'title'  => '',
                'status' => 0,
                'detail' => '',
            ],
            $apiProblem
        );
    }
}
