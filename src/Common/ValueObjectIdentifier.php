<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Common;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class ValueObjectIdentifier
{
    final private function __construct(public readonly UuidInterface $value)
    {
    }

    /** @return static */
    public static function new(): self
    {
        return new static(Uuid::uuid4());
    }

    /** @return static */
    public static function fromBytes(string $bytes): self
    {
        return new static(Uuid::fromBytes($bytes));
    }

    /** @return static */
    public static function fromString(string $uuid): self
    {
        return new static(Uuid::fromString($uuid));
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }
}
