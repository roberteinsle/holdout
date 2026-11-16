<?php

namespace App\Modules\Inventory\Filament\Widgets;

use App\Modules\Inventory\Filament\Actions\TakeOutAction;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Models\Batch;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dashboard: what has expired or expires within the household's "eat soon"
 * window, earliest first, with "take out" right there.
 */
class EatSoonWidget extends TableWidget
{
    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $days = BatchResource::eatSoonDays();

        return $table
            ->heading(__('Eat soon'))
            ->description(__('Expired, or expiring within :days days.', ['days' => $days]))
            ->query(fn (): Builder => Batch::query()
                ->forHousehold(auth()->user()->household_id)
                ->with(['product', 'location'])
                ->whereNotNull('best_before')
                ->whereDate('best_before', '<=', today()->addDays($days)->toDateString()))
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('Product'))
                    ->description(fn (Batch $record): ?string => BatchResource::details($record)),
                BatchResource::bestBeforeColumn()->sortable(false),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByBestBefore())
            ->recordUrl(fn (Batch $record): string => BatchResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                TakeOutAction::make()->iconButton(),
            ])
            ->headerActions([
                Action::make('all')
                    ->label(__('All batches'))
                    ->link()
                    ->url(BatchResource::getUrl('index')),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateHeading(__('Nothing to eat soon'))
            ->emptyStateDescription(__('No batch expires within the next :days days.', ['days' => $days]));
    }
}
