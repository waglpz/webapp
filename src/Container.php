<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Dice\Dice;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    private Dice $dice;

    public function __construct(Dice $dice)
    {
        $this->dice = $dice;
    }

    public function get(string $id): object
    {
        if ($this->has($id)) {
            return $this->dice->create($id);
        }

        throw new class ('Could not instantiate ' . $id) extends \Exception implements NotFoundExceptionInterface {
        };
    }

    public function has(string $id): bool
    {
        // php-cs:disable
        return \class_exists($id) || $this->dice->getRule($id) !== $this->dice->getRule('*'); //php-cs:enable
    }
}
