<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Common\Ui;

use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\Common\Assert\Assert;
use Waglpz\Webapp\Common\Db\DbHelperFunctions;
use Waglpz\Webapp\Common\Exception\NotFoundInDatabase;

use function Waglpz\Webapp\dataFromRequest;
use function Waglpz\Webapp\isSubset;

abstract class BaseAsserts
{
    public const int MAX_INT_VALUE = 2147483647; // Maximum value for int(11)

    /** @var array<string, bool|float|int|string|null> */
    protected array $inputData = [];

    /** @var array<string, bool|float|int|string|null> */
    protected array $validData = [];

    final public function __construct(
        public readonly DbHelperFunctions $dbHelperFunctions,
    ) {
    }

    abstract public function tableName(): string;

    abstract public function idPkName(): string;

    /**
     * @param array<string, bool|float|int|string|null>|ServerRequestInterface $inputData
     *
     * @throws \JsonException
     */
    public function withInputData(array|ServerRequestInterface $inputData): static
    {
        $me = new static($this->dbHelperFunctions);

        if ($inputData instanceof ServerRequestInterface) {
            $body = $inputData->getBody();
            $body->rewind();
            $request = $inputData->withBody($body);
            /* @phpstan-ignore-next-line */
            $me->inputData = dataFromRequest($request);

            return $me;
        }

        $me->inputData = $inputData;

        return $me;
    }

    public function assertInputDataIsNotEmpty(): static
    {
        Assert::isNonEmptyMap($this->inputData, 'The received data should not be empty.');

        return $this;
    }

    /** @param array<int,string> $allowedFields */
    public function assertAllFieldsExpected(array $allowedFields): static
    {
        $unexpectedParams = isSubset($this->inputData, $allowedFields);
        Assert::isEmpty($unexpectedParams, 'Unexpected parameters received: ' . \implode(', ', $unexpectedParams));

        return $this;
    }

    /**
     * @param array<string, bool|float|int|string|null>|null $mergeWith
     *
     * @return array<string, bool|float|int|string|null>
     */
    public function validData(array|null $mergeWith = null): array
    {
        // FIXME: Add exception for the case when conflict occurs
        if ($mergeWith !== null) {
            return \array_replace($this->validData, $mergeWith);
        }

        return $this->validData;
    }

    /** @return array<string, bool|float|int|string> */
    public function validDataColumns(string ...$name): array
    {
        $o = [];
        foreach ($name as $_name) {
            if (! isset($this->validData[$_name])) {
                continue;
            }

            $o[$_name] = $this->validData[$_name];
        }

        return $o;
    }

    /** @throws NotFoundInDatabase */
    public function assertId(bool $checkExistence): static
    {
        Assert::keyExists($this->inputData, $this->idPkName(), 'Value for ' . $this->idPkName() . ' is required.');
        $message = 'Invalid parameter "' . $this->idPkName() . '". Should be a positive integer between 1 and '
            . self::MAX_INT_VALUE . '.';
        Assert::integerish($this->inputData[$this->idPkName()], '1: ' . $message);
        $idPkValue = (int) $this->inputData[$this->idPkName()];
        Assert::positiveInteger($idPkValue, '2: ' . $message);
        $this->validData[$this->idPkName()] = $this->inputData[$this->idPkName()];

        if (! $checkExistence) {
            return $this;
        }

        if (! $this->dbHelperFunctions->existByIdInTable($this->tableName(), $this->idPkName(), $idPkValue)) {
            throw new NotFoundInDatabase(\sprintf('Dataset with ID %d not found in the database.', $idPkValue));
        }

        return $this;
    }

    /** @throws NotFoundInDatabase */
    public function assertOptionalId(bool $checkExistence): static
    {
        if (isset($this->inputData[$this->idPkName()])) {
            return $this->assertId($checkExistence);
        }

        return $this;
    }
}
