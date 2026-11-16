<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Enums\ExpiryStatus;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\CreateBatch;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\EditBatch;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\ListBatches;
use App\Modules\Inventory\Filament\Resources\Products\Pages\CreateProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\EditProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\ListProducts;
use App\Modules\Inventory\Filament\Resources\Products\RelationManagers\BatchesRelationManager;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Product;
use App\Registries\NavigationRegistry;
use Database\Seeders\CategorySeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class BatchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-16 10:00:00');
        $this->seed(CategorySeeder::class);
        $this->user = User::factory()->for(Household::factory()->state(['eat_soon_days' => 60]))->create();
        $this->actingAs($this->user);
        $this->product = Product::factory()->for($this->user->household)->create([
            'name' => 'Spaghetti',
            'category_id' => Category::query()->where('key', 'grains')->value('id'),
        ]);
    }

    public function test_the_batches_page_is_registered_through_the_navigation_registry(): void
    {
        $this->assertContains(BatchResource::class, app(NavigationRegistry::class)->resources());
        $this->get(BatchResource::getUrl('index'))->assertOk()->assertSee('No batches yet');
    }

    public function test_a_batch_is_created_for_a_product_of_the_household(): void
    {
        Livewire::test(CreateBatch::class)
            ->fillForm(['product_id' => $this->product->id, 'quantity' => 3, 'best_before' => '2027-05-31', 'note' => 'Cellar'])
            ->call('create')
            ->assertHasNoFormErrors();

        $batch = Batch::query()->sole();
        $this->assertTrue($batch->product->is($this->product));
        $this->assertSame(3, $batch->quantity);
        $this->assertSame('2027-05-31', $batch->best_before->toDateString());
        $this->assertSame('3 × 500 g', $batch->quantityLabel());
    }

    public function test_input_is_validated_and_other_households_products_are_refused(): void
    {
        $foreign = Product::factory()->create();

        Livewire::test(CreateBatch::class)
            ->fillForm(['product_id' => $foreign->id, 'quantity' => 0, 'best_before' => '1999-01-01', 'note' => str_repeat('x', 151)])
            ->call('create')
            ->assertHasFormErrors(['product_id', 'quantity', 'best_before', 'note']);

        $this->assertDatabaseCount('batches', 0);
    }

    public function test_the_best_before_date_is_optional(): void
    {
        Livewire::test(CreateBatch::class)
            ->fillForm(['product_id' => $this->product->id, 'quantity' => 1, 'best_before' => null])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(Batch::query()->sole()->best_before);
    }

    public function test_the_expiry_status_follows_the_eat_soon_window(): void
    {
        $today = today();

        $this->assertSame(ExpiryStatus::Expired, ExpiryStatus::for($today->copy()->subDay(), 60));
        $this->assertSame(ExpiryStatus::EatSoon, ExpiryStatus::for($today->copy(), 60));
        $this->assertSame(ExpiryStatus::EatSoon, ExpiryStatus::for($today->copy()->addDays(60), 60));
        $this->assertSame(ExpiryStatus::Fresh, ExpiryStatus::for($today->copy()->addDays(61), 60));
        $this->assertSame(ExpiryStatus::NoDate, ExpiryStatus::for(null, 60));
    }

    public function test_the_list_shows_the_households_batches_earliest_first_and_filters_by_status(): void
    {
        $later = Batch::factory()->for($this->product)->create(['best_before' => '2028-01-01']);
        $soon = Batch::factory()->for($this->product)->create(['best_before' => '2026-12-01']);
        $expired = Batch::factory()->for($this->product)->create(['best_before' => '2026-10-01']);
        $undated = Batch::factory()->for($this->product)->create(['best_before' => null]);
        $foreign = Batch::factory()->create();

        Livewire::test(ListBatches::class)
            ->assertCanSeeTableRecords([$expired, $soon, $later, $undated], inOrder: true)
            ->assertCanNotSeeTableRecords([$foreign])
            ->filterTable('status', ExpiryStatus::EatSoon->value)
            ->assertCanSeeTableRecords([$soon])
            ->assertCanNotSeeTableRecords([$expired, $later, $undated]);

        Livewire::test(ListBatches::class)
            ->filterTable('status', ExpiryStatus::Expired->value)
            ->assertCanSeeTableRecords([$expired])
            ->assertCanNotSeeTableRecords([$soon, $later, $undated]);
    }

    public function test_batches_are_edited_and_deleted_within_the_household_only(): void
    {
        $mine = Batch::factory()->for($this->product)->create(['quantity' => 2]);
        $foreign = Batch::factory()->create();

        Livewire::test(EditBatch::class, ['record' => $mine->getRouteKey()])
            ->fillForm(['quantity' => 5])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(5, $mine->fresh()->quantity);

        $this->get(BatchResource::getUrl('edit', ['record' => $foreign]))->assertNotFound();

        Livewire::test(EditBatch::class, ['record' => $mine->getRouteKey()])->callAction('delete');
        $this->assertModelMissing($mine);
    }

    public function test_batches_are_managed_on_the_product_page(): void
    {
        $existing = Batch::factory()->for($this->product)->create(['quantity' => 2, 'best_before' => '2027-01-01']);

        $this->get(EditProduct::getUrl(['record' => $this->product]))->assertOk()->assertSeeLivewire(BatchesRelationManager::class);

        Livewire::test(BatchesRelationManager::class, ['ownerRecord' => $this->product, 'pageClass' => EditProduct::class])
            ->assertCanSeeTableRecords([$existing])
            ->callAction(TestAction::make('create')->table(), ['quantity' => 4, 'best_before' => '2027-08-01'])
            ->assertHasNoFormErrors()
            ->callAction(TestAction::make('edit')->table($existing), ['quantity' => 1])
            ->assertHasNoFormErrors();

        $this->assertSame(1, $existing->fresh()->quantity);
        $this->assertSame([1, 4], $this->product->batches()->orderBy('quantity')->pluck('quantity')->all());
    }

    public function test_the_product_list_shows_the_packages_in_stock(): void
    {
        Batch::factory()->for($this->product)->create(['quantity' => 2]);
        Batch::factory()->for($this->product)->create(['quantity' => 3]);
        $empty = Product::factory()->for($this->user->household)->create(['name' => 'Rice']);

        Livewire::test(ListProducts::class)
            ->assertTableColumnFormattedStateSet('batches_sum_quantity', '5 ×', $this->product)
            ->assertTableColumnFormattedStateSet('batches_sum_quantity', '0 ×', $empty);
    }

    public function test_after_creating_a_product_its_page_opens_to_add_batches(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Rice', 'category_id' => $this->product->category_id, 'package_value' => 1, 'package_unit' => 'kg'])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(EditProduct::getUrl(['record' => Product::query()->where('name', 'Rice')->sole()]));
    }

    public function test_deleting_a_product_deletes_its_batches(): void
    {
        $batch = Batch::factory()->for($this->product)->create();

        $this->product->delete();

        $this->assertModelMissing($batch);
    }
}
