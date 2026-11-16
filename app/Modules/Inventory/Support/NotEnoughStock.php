<?php

namespace App\Modules\Inventory\Support;

use RuntimeException;

/** More packages were to be taken out than the batch still has. */
class NotEnoughStock extends RuntimeException
{
    public function __construct(public readonly int $left)
    {
        parent::__construct("Only {$left} packages left.");
    }
}
