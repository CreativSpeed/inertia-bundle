<?php

namespace Creativspeed\InertiaBundle\DTO;

use Closure;

class Prop
{
    public function __construct(
        private readonly mixed $value,
        private readonly bool $isLazy = false,
        private readonly bool $isAlways = false,
        private readonly bool $isDeferred = false,
        private readonly ?string $group = null
    ) {}

    public static function lazy(Closure $callback): self
    {
        return new self($callback, isLazy: true);
    }

    public static function always(mixed $value): self
    {
        return new self($value, isAlways: true);
    }

    public static function defer(Closure $callback, ?string $group = null): self
    {
        return new self($callback, isDeferred: true, group: $group);
    }

    public function __invoke(): mixed
    {
        $value = $this->value;
        return $value instanceof Closure ? $value() : $value;
    }

    public function isLazy(): bool
    {
        return $this->isLazy;
    }

    public function isAlways(): bool
    {
        return $this->isAlways;
    }

    // Note: Deferred implementation depends on how your frontend adapter handles the marker.
    // For V2, we often just treat it as "Lazy" but grouped.
    public function isDeferred(): bool
    {
        return $this->isDeferred;
    }
}
