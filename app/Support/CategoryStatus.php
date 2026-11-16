<?php

namespace App\Support;

/**
 * One category: what the household needs (target) against what it has.
 */
final readonly class CategoryStatus
{
    public function __construct(
        public SupplyTarget $target,
        public float $stock,
        public float $expired,
    ) {}

    /** Share of the target covered by usable stock, 0 to 100 (capped). */
    public function percent(): int
    {
        return $this->target->total > 0 ? (int) min(100, floor($this->stock / $this->target->total * 100)) : 100;
    }

    /** Whole days the usable stock lasts for the household. */
    public function days(): int
    {
        return $this->target->perDay > 0 ? (int) floor($this->stock / $this->target->perDay + 1e-9) : 0;
    }

    /** What is still missing to reach the target (0 when enough). */
    public function missing(): float
    {
        return max(0.0, $this->target->total - $this->stock);
    }

    public function level(): StockLevel
    {
        return StockLevel::for($this->stock, $this->target->total);
    }
}
