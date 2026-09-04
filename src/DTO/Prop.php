<?php

namespace CreativSpeed\InertiaBundle\DTO;

use Closure;

/**
 * Wraps a prop value with Inertia's evaluation/inclusion semantics:
 *
 *  - lazy      only resolved/included on a partial reload that explicitly requests it
 *  - always    always included, even on a partial reload that would otherwise exclude it
 *  - defer     excluded from the initial page load; the client fetches it in a
 *              follow-up request, optionally batched by group
 *  - merge     the client merges this prop into the existing value instead of
 *              replacing it (e.g. appending to an infinite-scroll list)
 *  - deepMerge same as merge, but merges nested arrays/objects recursively
 *
 * merge/deepMerge can be combined with defer via the fluent methods, e.g.
 * `$inertia->defer(fn () => $this->loadMore(), 'feed')->merge()`.
 */
final class Prop
{
    private function __construct(
        private readonly mixed $value,
        private readonly bool $isLazy = false,
        private readonly bool $isAlways = false,
        private readonly bool $isDeferred = false,
        private readonly bool $isMergeProp = false,
        private readonly bool $isDeepMergeProp = false,
        private readonly ?string $group = null,
        /** @var string[] */
        private readonly array $matchOn = [],
    ) {
    }

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

    public static function merge(mixed $value, array $matchOn = []): self
    {
        return new self($value, isMergeProp: true, matchOn: $matchOn);
    }

    public static function deepMerge(mixed $value, array $matchOn = []): self
    {
        return new self($value, isDeepMergeProp: true, matchOn: $matchOn);
    }

    /** Fluent: mark an existing Prop (e.g. a deferred one) as a merge prop too. */
    public function merged(array $matchOn = []): self
    {
        return new self(
            $this->value,
            $this->isLazy,
            $this->isAlways,
            $this->isDeferred,
            isMergeProp: true,
            group: $this->group,
            matchOn: $matchOn ?: $this->matchOn,
        );
    }

    /** Fluent: mark an existing Prop as a deep-merge prop too. */
    public function deepMerged(array $matchOn = []): self
    {
        return new self(
            $this->value,
            $this->isLazy,
            $this->isAlways,
            $this->isDeferred,
            isDeepMergeProp: true,
            group: $this->group,
            matchOn: $matchOn ?: $this->matchOn,
        );
    }

    public function __invoke(): mixed
    {
        return $this->value instanceof Closure ? ($this->value)() : $this->value;
    }

    public function isLazy(): bool
    {
        return $this->isLazy;
    }

    public function isAlways(): bool
    {
        return $this->isAlways;
    }

    public function isDeferred(): bool
    {
        return $this->isDeferred;
    }

    public function isMerge(): bool
    {
        return $this->isMergeProp || $this->isDeepMergeProp;
    }

    public function isDeepMerge(): bool
    {
        return $this->isDeepMergeProp;
    }

    public function getGroup(): string
    {
        return $this->group ?? 'default';
    }

    /** @return string[] */
    public function getMatchOn(): array
    {
        return $this->matchOn;
    }
}
