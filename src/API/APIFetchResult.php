<?php

declare(strict_types=1);

namespace Waglpz\Webapp\API;

use Psr\Http\Message\ResponseInterface;

final class APIFetchResult
{
    public const OK = 'OK';
    public const CE = 'CE';
    public const SE = 'SE';
    public const TO = 'TO';

    private ResponseInterface $response;
    private string $status;
    /** @var ?array<mixed>  */
    private ?array $data;
    private APIProblem $apiProblem;

    private function __construct()
    {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        $new           = new self();
        $new->response = $response;

        $status = $new->response->getStatusCode();

        $body     = $response->getBody();
        $bodySize = $body->getSize();

        if ($bodySize === null || $bodySize < 1) {
            $data = [];
        } else {
            $body->rewind();
            $data = \json_decode(
                $body->getContents(),
                true,
                512,
                \JSON_THROW_ON_ERROR | \JSON_BIGINT_AS_STRING
            );
        }

        if ((200 <= $status) && ($status <= 299)) {
            $new->status = self::OK;
            $new->data   = (array) $data;

            return $new;
        }

        if ((400 <= $status) && ($status <= 499)) {
            $new->status = self::CE;
        } elseif ((500 <= $status) && ($status <= 599)) {
            $new->status = self::SE;
        } else {
            throw new \UnexpectedValueException('Status "%d" was unexpected.');
        }

        $new->apiProblem = APIProblem::fromArray((array) $data);

        return $new;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function ok(): bool
    {
        return $this->status === self::OK;
    }

    public function clientError(): bool
    {
        return $this->status === self::CE;
    }

    public function serverError(): bool
    {
        return $this->status === self::SE;
    }

    /** @return array<mixed> */
    public function data(): array
    {
        return $this->data ?? [];
    }

    public function apiProblem(): APIProblem
    {
        if ($this->status === self::OK) {
            throw new \BadMethodCallException(
                'Method should not called in contexts where status not corresponding to a Api problem.'
            );
        }

        return $this->apiProblem;
    }
}
