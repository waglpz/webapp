<?php /** @noinspection PhpMissingFieldTypeInspection */

declare(strict_types=1);

namespace Waglpz\Webapp\Security;

use Exception;

final class Forbidden extends Exception
{
    /** @codingStandardsIgnoreStart */
    /** @var string */
    protected $message = 'Unberechtigt';
    /** @var int */
    protected $code       = 403;
    /** @codingStandardsIgnoreEnd */
}
