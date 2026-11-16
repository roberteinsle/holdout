<?php

namespace App\Modules\Inventory\Observers;

use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Support\StockLedger;

/**
 * Records every change of a batch's packages in the history, whichever page
 * or action made it. Taking out uses StockLedger::takeOut() and saves quietly.
 */
class BatchObserver
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function created(Batch $batch): void
    {
        $this->ledger->record($batch, MovementType::Added, $batch->quantity);
    }

    public function updated(Batch $batch): void
    {
        if (! $batch->wasChanged('quantity')) {
            return;
        }

        $delta = $batch->quantity - (int) $batch->getOriginal('quantity');

        if ($delta !== 0) {
            $this->ledger->record($batch, MovementType::Corrected, $delta);
        }
    }

    public function deleting(Batch $batch): void
    {
        if ($batch->quantity > 0) {
            $this->ledger->record($batch, MovementType::Corrected, -$batch->quantity);
        }
    }
}
