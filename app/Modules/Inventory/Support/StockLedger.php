<?php

namespace App\Modules\Inventory\Support;

use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Movement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Writes the stock history. Adding, changing and deleting a batch are recorded
 * by the BatchObserver through record(); taking packages out goes through
 * takeOut(), which changes the batch itself and records one entry.
 */
class StockLedger
{
    public function record(Batch $batch, MovementType $type, int $quantity, ?string $note = null): Movement
    {
        return Movement::query()->create([
            'product_id' => $batch->product_id,
            'batch_id' => $batch->getKey(),
            'user_id' => auth()->id(),
            'type' => $type,
            'quantity' => $quantity,
            'note' => $note,
        ]);
    }

    /**
     * Take packages out of a batch. An emptied batch is deleted; its history
     * stays. Throws when more is asked for than is left (someone else may have
     * taken some meanwhile).
     */
    public function takeOut(Batch $batch, int $quantity, MovementType $reason, ?string $note = null): Movement
    {
        if (! in_array($reason, MovementType::takeOutReasons(), true) || $quantity < 1) {
            throw new InvalidArgumentException('Invalid take-out.');
        }

        return DB::transaction(function () use ($batch, $quantity, $reason, $note): Movement {
            $current = Batch::query()->lockForUpdate()->find($batch->getKey());

            if ($current === null || $quantity > $current->quantity) {
                throw new NotEnoughStock($current?->quantity ?? 0);
            }

            $movement = $this->record($current, $reason, -$quantity, $note);
            $remaining = $current->quantity - $quantity;

            // Quietly: the observer must not record these changes a second time.
            if ($remaining === 0) {
                $current->deleteQuietly();
            } else {
                $current->forceFill(['quantity' => $remaining])->saveQuietly();
            }

            return $movement;
        });
    }
}
