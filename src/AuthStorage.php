<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use InvalidArgumentException;
use Waglpz\Webapp\Security\Rollen;

/**
 * @property array<string> $roles
 * @property string        $email
 * @property string        $id
 * @property ?string       $name    = null
 * @property ?string        $picture = null
 */
final class AuthStorage
{
    /** @return mixed */
    public function __get(string $name)
    {
        if ($this->__isset($name)) {
            return $_SESSION['auth_storage'][$name];
        }

        // return a default role unless roles wasn't set
        if ($name === 'roles') {
            return [Rollen::UNBEKANNT];
        }

        if ($name === 'picture' || $name === 'name') {
            return null;
        }

        if ($name === 'email') {
            $message = 'Invalid email address or unauthorized user.';
        } elseif ($name === 'id') {
            $message = 'Invalid user ID or unauthorized user.';
        } else {
            $message = 'Invalid key given "' . $name . '".';
        }

        throw new InvalidArgumentException($message);
    }

    /** @param mixed $data */
    public function __set(string $name, $data): void
    {
        /** @noinspection NotOptimalIfConditionsInspection */
        if ($this->__isset($name) && $_SESSION['auth_storage'][$name] !== $data) {
            throw new InvalidArgumentException('Auth storage already initialized with attribute "' . $name . '".');
        }

        $_SESSION['auth_storage'][$name] = $data;
    }

    public function __isset(string $name): bool
    {
        return isset($_SESSION['auth_storage'][$name]);
    }

    /** @param array<string,mixed> $data */
    public function assign(array $data): void
    {
        foreach ($data as $name => $value) {
            $this->__set($name, $value);
        }
    }

    public function reset(): void
    {
        $_SESSION['auth_storage'] = null;
    }

    public function hasSingleRolle(string $rolle): bool
    {
        return $this->hasRolle($rolle) && \count($this->roles) === 1;
    }

    public function hasRolle(string $rolle): bool
    {
        return \in_array($rolle, $this->roles, true);
    }
}
