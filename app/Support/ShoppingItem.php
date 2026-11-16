<?php

namespace App\Support;

/** One line of the shopping list: how much to buy of a category and how many days it is short. */
final readonly class ShoppingItem
{
    public function __construct(
        public CategoryStatus $status,
        public float $amount,
        public int $daysShort,
    ) {}
}
