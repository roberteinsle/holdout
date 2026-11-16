<?php

namespace App\Registries;

/**
 * Stock of one category in its unit (kg or l). Usable counts towards the
 * target; expired is still on the shelf but past its best-before date.
 */
final readonly class StockAmount
{
    public function __construct(
        public float $usable = 0.0,
        public float $expired = 0.0,
    ) {}

    public function plus(self $other): self
    {
        return new self($this->usable + $other->usable, $this->expired + $other->expired);
    }
}
