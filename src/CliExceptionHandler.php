<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

/** @codeCoverageIgnore */
final class CliExceptionHandler
{
    private ?string $logDirectory;

    public function __construct(?string $logDirectory = null)
    {
        $this->logDirectory = $logDirectory;
    }

    public function __invoke(\Throwable $exception): void
    {
        $code              = $exception->getCode();
        $inputStreamHandle = \fopen('php://input', 'wb+');
        \assert(\is_resource($inputStreamHandle));
        \fseek($inputStreamHandle, 0, \SEEK_SET);
        $input = \stream_get_contents($inputStreamHandle);
        \fclose($inputStreamHandle);
        $date       = \date('Y-m-d H:i:s');
        $loggingDir = $this->logDirectory ?? '/tmp';

        \file_put_contents(
            $loggingDir . '/cli.' . \APP_ENV . '.log',
            $date . ' [ERROR ' . $code . '] ' . $exception->getMessage() . \PHP_EOL
            . $exception->getTraceAsString() . \PHP_EOL
            . $date . ' [SERVER] ' . \preg_replace('#\s+#', ' ', \print_r($_SERVER, true)) . \PHP_EOL
            . $date . ' [INPUT] ' . $input . \PHP_EOL,
            \FILE_APPEND
        );
    }
}
