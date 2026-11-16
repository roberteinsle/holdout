<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\CreateBatch;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\EditBatch;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\ListBatches;
use App\Modules\Inventory\Filament\Resources\Movements\MovementResource;
use App\Modules\Inventory\Filament\Resources\Movements\Pages\ListMovements;
use App\Modules\Inventory\Filament\Resources\Products\Pages\EditProduct;
use App\Modules\Inventory\Filament\Resources\Products\RelationManagers\BatchesRelationManager;
use App\Modules\Inventory\Filament\Resources\Products\RelationManagers\MovementsRelationManager;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Movement;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Support\NotEnoughStock;
use App\Modules\Inventory\Support\StockLedger;
use App\Registries\NavigationRegistry;
use Database\Seeders\CategorySeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
        $this->user = User::factory()->for(Household::factory())->create(['name' => 'Alex']);
        $this->actingAs($this->user);
        $this->product = Product::factory()->for($this->user->household)->create(['name' => 'Spaghetti']);
    }

    private function batch(int $quantity = 5): Batch
    {
        return Batch::factory()->for($this->product)->create(['quantity' => $quantity]);
    }

    public function test_the_history_page_is_registered_and_read_only(): void
    {
        $this->assertContains(MovementResource::class, app(NavigationRegistry::class)->resources());
        $this->get(MovementResource::getUrl('index'))->assertOk()->assertSee('Nothing has happened yet');
        $this->assertFalse(MovementResource::canCreate());

        $movement = Movement::factory()->for($this->product)->create();
        $this->assertTrue($this->user->can('view', $movement));
        $this->assertFalse($this->user->can('update', $movement));
        $this->assertFalse($this->user->can('delete', $movement));
    }

    public function test_adding_a_batch_is_recorded_with_the_person(): void
    {
        Livewire::test(CreateBatch::class)
            ->fillForm(['product_id' => $this->product->id, 'quantity' => 4])
            ->call('create')
            ->assertHasNoFormErrors();

        $movement = Movement::query()->sole();
        $this->assertSame(MovementType::Added, $movement->type);
        $this->assertSame(4, $movement->quantity);
        $this->assertTrue($movement->user->is($this->user));
        $this->assertSame('+4 × 500 g', $movement->quantityLabel());
    }

    public function test_changing_the_amount_by_hand_is_recorded_as_a_correction(): void
    {
        $batch = $this->batch(5);

        Livewire::test(EditBatch::class, ['record' => $batch->getRouteKey()])
            ->fillForm(['quantity' => 3, 'note' => 'counted'])
            ->call('save')
            ->assertHasNoFormErrors();

        $correction = Movement::query()->where('type', MovementType::Corrected)->sole();
        $this->assertSame(-2, $correction->quantity);

        // Changing only the note records nothing.
        Livewire::test(EditBatch::class, ['record' => $batch->getRouteKey()])
            ->fillForm(['note' => 'cellar'])
            ->call('save');
        $this->assertSame(1, Movement::query()->where('type', MovementType::Corrected)->count());
    }

    public function test_deleting_a_batch_keeps_its_history(): void
    {
        $batch = $this->batch(2);

        Livewire::test(EditBatch::class, ['record' => $batch->getRouteKey()])->callAction('delete');

        $this->assertModelMissing($batch);
        $movements = Movement::query()->orderBy('id')->get();
        $this->assertSame([2, -2], $movements->pluck('quantity')->all());
        $this->assertNull($movements->last()->batch_id);
        $this->assertSame(MovementType::Corrected, $movements->last()->type);
    }

    public function test_taking_out_from_the_list_lowers_the_batch_and_records_who_and_why(): void
    {
        $batch = $this->batch(5);

        Livewire::test(ListBatches::class)
            ->callAction(TestAction::make('takeOut')->table($batch), ['quantity' => 2, 'type' => MovementType::Consumed->value, 'note' => 'for the soup'])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(3, $batch->fresh()->quantity);
        $out = Movement::query()->where('type', MovementType::Consumed)->sole();
        $this->assertSame(-2, $out->quantity);
        $this->assertSame('for the soup', $out->note);
        $this->assertTrue($out->user->is($this->user));
        // No extra correction from the observer.
        $this->assertSame(0, Movement::query()->where('type', MovementType::Corrected)->count());
    }

    public function test_taking_out_everything_removes_the_batch_but_not_its_history(): void
    {
        $batch = $this->batch(2);

        Livewire::test(BatchesRelationManager::class, ['ownerRecord' => $this->product, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make('takeOut')->table($batch), ['quantity' => 2, 'type' => MovementType::Discarded->value])
            ->assertHasNoFormErrors();

        $this->assertModelMissing($batch);
        $this->assertSame([MovementType::Added, MovementType::Discarded], Movement::query()->orderBy('id')->pluck('type')->all());
        $this->assertSame(0, Movement::query()->where('type', MovementType::Corrected)->count());
    }

    public function test_taking_out_from_the_batch_page_returns_to_the_list_when_empty(): void
    {
        $batch = $this->batch(3);

        Livewire::test(EditBatch::class, ['record' => $batch->getRouteKey()])
            ->callAction('takeOut', ['quantity' => 1, 'type' => MovementType::Consumed->value])
            ->assertHasNoFormErrors()
            ->assertSchemaStateSet(['quantity' => 2])
            ->callAction('takeOut', ['quantity' => 2, 'type' => MovementType::Consumed->value])
            ->assertRedirect(ListBatches::getUrl());

        $this->assertModelMissing($batch);
    }

    public function test_more_than_is_left_cannot_be_taken_out_and_the_reason_is_checked(): void
    {
        $batch = $this->batch(2);

        Livewire::test(ListBatches::class)
            ->callAction(TestAction::make('takeOut')->table($batch), ['quantity' => 3, 'type' => MovementType::Consumed->value])
            ->assertHasFormErrors(['quantity']);

        Livewire::test(ListBatches::class)
            ->callAction(TestAction::make('takeOut')->table($batch), ['quantity' => 1, 'type' => MovementType::Added->value])
            ->assertHasFormErrors(['type']);

        $this->assertSame(2, $batch->fresh()->quantity);
    }

    public function test_the_ledger_refuses_when_someone_else_took_packages_meanwhile(): void
    {
        $batch = $this->batch(2);
        $stale = Batch::query()->find($batch->id);

        app(StockLedger::class)->takeOut($batch, 2, MovementType::Consumed);

        $this->expectException(NotEnoughStock::class);
        app(StockLedger::class)->takeOut($stale, 1, MovementType::Consumed);
    }

    public function test_the_history_lists_the_households_entries_newest_first_and_filters(): void
    {
        $this->travelTo(now()->subDay());
        $batch = $this->batch(5);
        $this->travelBack();
        app(StockLedger::class)->takeOut($batch, 1, MovementType::Consumed);

        $added = Movement::query()->where('type', MovementType::Added)->sole();
        $consumed = Movement::query()->where('type', MovementType::Consumed)->sole();
        $foreign = Movement::factory()->create();

        Livewire::test(ListMovements::class)
            ->assertCanSeeTableRecords([$consumed, $added], inOrder: true)
            ->assertCanNotSeeTableRecords([$foreign])
            ->assertSee('−1 × 500 g · Alex')
            ->filterTable('type', MovementType::Added->value)
            ->assertCanSeeTableRecords([$added])
            ->assertCanNotSeeTableRecords([$consumed]);

        Livewire::test(MovementsRelationManager::class, ['ownerRecord' => $this->product, 'pageClass' => EditProduct::class])
            ->assertCanSeeTableRecords([$consumed, $added]);
    }

    public function test_entries_stay_when_the_person_is_removed(): void
    {
        $batch = $this->batch(1);
        $sam = User::factory()->for($this->user->household)->create(['name' => 'Sam']);
        $this->actingAs($sam);
        app(StockLedger::class)->takeOut($batch, 1, MovementType::Consumed);

        $sam->delete();

        $entry = Movement::query()->where('type', MovementType::Consumed)->sole();
        $this->assertNull($entry->user_id);
        $this->assertStringContainsString('Unknown', MovementResource::details($entry));
    }
}
