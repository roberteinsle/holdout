<?php

namespace App\Modules\Inventory\Filament\Widgets;

use App\Modules\Inventory\Filament\Resources\Movements\MovementResource;
use App\Modules\Inventory\Models\Movement;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Dashboard: the latest entries of the stock history. */
class RecentActivityWidget extends TableWidget
{
    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Recently'))
            ->query(fn (): Builder => Movement::query()
                ->forHousehold(auth()->user()->household_id)
                ->with(['product', 'user'])
                ->latest()
                ->latest('id')
                ->limit(5))
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('Product'))
                    ->description(fn (Movement $record): string => MovementResource::details($record)),
                MovementResource::typeColumn(),
            ])
            ->headerActions([
                Action::make('all')
                    ->label(__('Full history'))
                    ->link()
                    ->url(MovementResource::getUrl('index')),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('Nothing has happened yet'));
    }
}
