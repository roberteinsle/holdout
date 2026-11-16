<?php

namespace App\Modules\Inventory\Filament\Widgets;

use App\Modules\Inventory\Filament\Pages\Scan;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use Filament\Widgets\Widget;

/** Dashboard: the two things done most often, as big buttons for the phone. */
class QuickActionsWidget extends Widget
{
    protected string $view = 'inventory::widgets.quick-actions';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        return [
            'scanUrl' => Scan::getUrl(),
            'addBatchUrl' => BatchResource::getUrl('create'),
        ];
    }
}
