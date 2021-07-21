<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\API;

use PHPUnit\Framework\TestCase;
use Waglpz\Webapp\API\APIProblem;

final class APIProblemTest extends TestCase
{
    /** @test */
    public function hasAPrivateConstructMethod(): void
    {
        $reflection = new \ReflectionClass(APIProblem::class);
        self::assertTrue($reflection->hasMethod('__construct'));
        $constructorMethodReflection = $reflection->getConstructor();
        self::assertNotNull($constructorMethodReflection);
        self::assertTrue($constructorMethodReflection->isPrivate());
    }

    /** @test */
    public function apiProblemWithEmptyData(): void
    {
        $apiProblem  = APIProblem::fromArray([]);
        $fact        = $apiProblem->toArray();
        $expectation = [
            'type'    => '',
            'title'   => '',
            'status'  => 0,
            'detail' => '',
        ];

        self::assertSame($expectation, $fact);
        self::assertCount(0, $apiProblem->problems());
        self::assertNull($apiProblem->problems()->getReturn());
    }

    /** @test */
    public function apiProblem(): void
    {
        $data       = [
            'type'     => '/url-to-a-problem-description',
            'title'    => 'Client data invalid',
            'status'   => 400,
            'detail'   => 'Attribute A and B invalid',
            'problems' => [
                [
                    'type'    => '/url-to-a-description-A-problem',
                    'title'   => 'Attribute A invalid',
                    'status'  => 400,
                    'detail' => 'Attribute A is empty',
                ],
                [
                    'type'    => '/url-to-a-description-B-problem',
                    'title'   => 'Attribute B invalid',
                    'status'  => 400,
                    'detail' => 'Attribute B min length should be 3',
                ],
            ],
        ];
        $apiProblem = APIProblem::fromArray($data);

        self::assertSame(400, $apiProblem->status());
        self::assertSame('/url-to-a-problem-description', $apiProblem->type());
        self::assertSame('Client data invalid', $apiProblem->title());
        self::assertSame('Attribute A and B invalid', $apiProblem->detail());

        $problems =            $apiProblem->problems();
        $problems->rewind();
        self::assertSame(
            [
                'type'    => '/url-to-a-description-A-problem',
                'title'   => 'Attribute A invalid',
                'status'  => 400,
                'detail' => 'Attribute A is empty',
            ],
            $apiProblem->problems()->current()->toArray()
        );
        $problems->next();
        self::assertSame(
            [
                'type'    => '/url-to-a-description-B-problem',
                'title'   => 'Attribute B invalid',
                'status'  => 400,
                'detail' => 'Attribute B min length should be 3',
            ],
            $problems->current()->toArray()
        );

        $fact = $apiProblem->toArray();

        self::assertSame($data, $fact);
    }
}
