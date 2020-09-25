<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use PHPUnit\Framework\TestCase;
use Waglpz\Webapp\AuthStorage;
use Waglpz\Webapp\Security\Rollen;

class AuthStorageTest extends TestCase
{
    private AuthStorage $storage;

    protected function setUp() : void
    {
        parent::setUp();

        $this->storage = new AuthStorage();
        $_SESSION      = null;
    }

    /** @test */
    public function setAndGetProperty() : void
    {
        $this->storage->email   = 'test@test.de';
        $this->storage->name    = 'tester';
        $this->storage->picture = '/images/logo.jpg';
        $this->storage->roles   = ['TESTER', 'USER'];
        /** @phpstan-ignore-next-line */
        $this->storage->key = 'value';

        self::assertSame('test@test.de', $this->storage->email);
        self::assertSame('test@test.de', $_SESSION['auth_storage']['email']);
        self::assertSame('tester', $this->storage->name);
        self::assertSame('tester', $_SESSION['auth_storage']['name']);
        self::assertSame('/images/logo.jpg', $this->storage->picture);
        self::assertSame('/images/logo.jpg', $_SESSION['auth_storage']['picture']);
        self::assertSame(['TESTER', 'USER'], $this->storage->roles);
        self::assertSame(['TESTER', 'USER'], $_SESSION['auth_storage']['roles']);
        self::assertSame('value', $this->storage->key);
        self::assertSame('value', $_SESSION['auth_storage']['key']);
    }

    /** @test */
    public function assignAndGetProperties() : void
    {
        $data = [
            'email'   => 'test@test.de',
            'name'    => 'tester',
            'picture' => '/images/logo.jpg',
            'roles'   => ['TESTER', 'USER'],
            'key'     => 'value',
        ];
        $this->storage->assign($data);

        self::assertSame('test@test.de', $this->storage->email);
        self::assertSame('test@test.de', $_SESSION['auth_storage']['email']);
        self::assertSame('tester', $this->storage->name);
        self::assertSame('tester', $_SESSION['auth_storage']['name']);
        self::assertSame('/images/logo.jpg', $this->storage->picture);
        self::assertSame('/images/logo.jpg', $_SESSION['auth_storage']['picture']);
        self::assertSame(['TESTER', 'USER'], $this->storage->roles);
        self::assertSame(['TESTER', 'USER'], $_SESSION['auth_storage']['roles']);
        /** @phpstan-ignore-next-line */
        self::assertSame('value', $this->storage->key);
        self::assertSame('value', $_SESSION['auth_storage']['key']);
    }

    /** @test */
    public function resetDoReset() : void
    {
        $data = [
            'email'   => 'test@test.de',
            'name'    => 'tester',
            'picture' => '/images/logo.jpg',
            'roles'   => ['TESTER', 'USER'],
            'key'     => 'value',
        ];
        $this->storage->assign($data);
        $this->storage->reset();
        self::assertFalse(isset($this->storage->email));
        self::assertFalse(isset($this->storage->name));
        self::assertFalse(isset($this->storage->picture));
        self::assertFalse(isset($this->storage->roles));
        self::assertFalse(isset($this->storage->key));
        self::assertNull($_SESSION['auth_storage']);
    }

    /** @test */
    public function getUnknownKey() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key given "wrong".');
        /** @phpstan-ignore-next-line */
        $this->storage->wrong;
    }

    /** @test */
    public function getEmailBeforeWasSettled() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address or unauthorized user.');
        $email = $this->storage->email;
    }

    /** @test */
    public function writeOnlyOnce() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Auth storage already initialized with attribute "id".');
        $this->storage->id = 'tester1@tester.de';
        /** @noinspection SuspiciousAssignmentsInspection */
        $this->storage->id = 'tester2@tester.de';
    }

    /** @test */
    public function getIdBeforeWasSettled() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid user ID or unauthorized user.');
        $id = $this->storage->id;
    }

    /** @test */
    public function getRolesBeforeWasSettled() : void
    {
        $roles = $this->storage->roles;
        self::assertSame([Rollen::UNBEKANNT], $roles);
    }

    /** @test */
    public function getNameBeforeWasSettled() : void
    {
        self::assertNull($this->storage->name);
    }

    /** @test */
    public function getPictureBeforeWasSettled() : void
    {
        self::assertNull($this->storage->picture);
    }

    /** @test */
    public function hasRole() : void
    {
        $this->storage->roles = [
            'ROLE_1',
            'ROLE_2',
            'ROLE_3',
        ];
        self::assertTrue($this->storage->hasRolle('ROLE_2'));
    }

    /** @test */
    public function hasOnlyOneRole() : void
    {
        $this->storage->roles = ['ROLE_2'];
        self::assertTrue($this->storage->hasSingleRolle('ROLE_2'));
    }

    /** @test */
    public function hasNotOnlyOneRole() : void
    {
        $this->storage->roles = [
            'ROLE_1',
            'ROLE_2',
            'ROLE_3',
        ];
        self::assertFalse($this->storage->hasSingleRolle('ROLE_2'));
    }
}
