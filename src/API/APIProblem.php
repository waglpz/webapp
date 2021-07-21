<?php

declare(strict_types=1);

namespace Waglpz\Webapp\API;

final class APIProblem
{
    private int $status;
    private string $type;
    private string $title;
    private string $detail;
    /** @var array<self>  */
    private array $problems;

    private function __construct()
    {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $new         = new self();
        $new->type   = $data['type'] ?? '';
        $new->title  = $data['title'] ?? '';
        $new->status = $data['status'] ?? 0;
        $new->detail = $data['detail'] ?? '';

        if (isset($data['problems']) && \is_array($data['problems']) && \count($data['problems']) > 0) {
            $new->problems = \array_map(
                static fn (array $problem): self => self::fromArray($problem),
                $data['problems']
            );
        }

        return $new;
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        $data = [
            'type'    => $this->type,
            'title'   => $this->title,
            'status'  => $this->status,
            'detail' => $this->detail,
        ];

        if (isset($this->problems)) {
            $data['problems'] = \array_map(static fn ($problem): array => $problem->toArray(), $this->problems);
        }

        return $data;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function detail(): string
    {
        return $this->detail;
    }

    /** @return \Generator<self> */
    public function problems(): \Generator
    {
        if (! isset($this->problems)) {
            return null;
        }

        foreach ($this->problems as $problem) {
            yield $problem;
        }
    }
}
