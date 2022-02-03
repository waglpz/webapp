<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Aidphp\Http\Emitter;
use Aidphp\Http\Response;
use Interop\Http\EmitterInterface;

final class ExceptionHandler implements ExceptionHandlerInvokable
{
    private ?string $logErrorsDir;
    /** @var array<mixed> */
    private ?array $anonymizeLog;

    /** @param array<mixed> $anonymizeLog */
    public function __construct(?string $logErrorsDir = null, ?array $anonymizeLog = null)
    {
        $this->logErrorsDir = $logErrorsDir;
        $this->anonymizeLog = $anonymizeLog;
    }

    public function __invoke(\Throwable $exception, ?EmitterInterface $emitter = null): void
    {
        $code              = $exception->getCode();
        $code              = $code < 100 || $code > 599 ? 500 : $code;
        $inputStreamHandle = \fopen('php://input', 'rb');
        \assert(\is_resource($inputStreamHandle));
        \fseek($inputStreamHandle, 0, \SEEK_SET);
        $payload = \stream_get_contents($inputStreamHandle);
        \fclose($inputStreamHandle);
        $date       = \date('Y-m-d H:i:s');
        $loggingDir = $this->logErrorsDir ?? '/tmp';

        if ($this->anonymizeLog !== null) {
            $GLOBALS = \array_replace_recursive($GLOBALS, $this->anonymizeLog);
        }

        \file_put_contents(
            $loggingDir . '/error.' . \APP_ENV . '.log',
            $date . ' [ERROR ' . $code . '] ' . $exception->getMessage() . \PHP_EOL
            . $exception->getTraceAsString() . \PHP_EOL
            . $date . ' [SERVER] ' . \preg_replace('#\s+#', ' ', \print_r($GLOBALS['_SERVER'], true)) . \PHP_EOL
            . $date . ' [PAYLOAD] ' . $payload . \PHP_EOL
            . $date . ' [POST] ' . \preg_replace('#\s+#', ' ', \print_r($GLOBALS['_POST'], true)) . \PHP_EOL
            . $date . ' [GET] ' . \preg_replace('#\s+#', ' ', \print_r($GLOBALS['_GET'], true)) . \PHP_EOL,
            \FILE_APPEND
        );
        $response = new Response($code);
        if ($emitter === null) {
            $emitter = new Emitter();
        }

        $emitter->emit($response);
    }
}
