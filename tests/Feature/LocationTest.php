<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\CreateBatch;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\EditBatch;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\ListBatches;
use App\Modules\Inventory\Filament\Resources\Locations\LocationResource;
use App\Modules\Inventory\Filament\Resources\Locations\Pages\ManageLocations;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Location;
use App\Modules\Inventory\Models\Product;
use App\Registries\NavigationRegistry;
use Database\Seeders\CategorySeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
        $this->user = User::factory()->for(Household::factory())->create();
        $this->actingAs($this->user);
        $this->product = Product::factory()->for($this->user->household)->create(['name' => 'Spaghetti']);
    }

    public function test_the_locations_page_is_registered_through_the_navigation_registry(): void
    {
        $this->assertContains(LocationResource::class, app(NavigationRegistry::class)->resources());
        $this->get(LocationResource::getUrl('index'))->assertOk()->assertSee('No locations yet');
    }

    public function test_a_location_is_created_for_the_household_and_names_are_unique_per_household(): void
    {
        Location::factory()->create(['name' => 'Cellar']);

        Livewire::test(ManageLocations::class)
            ->callAction('create', ['name' => 'Cellar', 'note' => 'Shelf on the left'])
            ->assertHasNoFormErrors();

        $location = Location::query()->where('household_id', $this->user->household_id)->sole();
        $this->assertSame('Cellar', $location->name);

        Livewire::test(ManageLocations::class)
            ->callAction('create', ['name' => 'Cellar'])
            ->assertHasFormErrors(['name' => 'unique']);

        Livewire::test(ManageLocations::class)
            ->callAction('create', ['name' => str_repeat('x', 101)])
            ->assertHasFormErrors(['name']);
    }

    public function test_locations_are_listed_edited_and_deleted_within_the_household_only(): void
    {
        $mine = Location::factory()->for($this->user->household)->create(['name' => 'Cellar']);
        $other = Location::factory()->create(['name' => 'Neighbour cellar']);
        Batch::factory()->for($this->product)->create(['location_id' => $mine->id, 'quantity' => 3]);

        Livewire::test(ManageLocations::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other])
            ->assertTableColumnFormattedStateSet('batches_sum_quantity', '3 ×', $mine)
            ->callAction(TestAction::make('edit')->table($mine), ['name' => 'Basement'])
            ->assertHasNoFormErrors();

        $this->assertSame('Basement', $mine->fresh()->name);
    }

    public function test_deleting_a_location_keeps_its_batches_without_a_location(): void
    {
        $location = Location::factory()->for($this->user->household)->create();
        $batch = Batch::factory()->for($this->product)->create(['location_id' => $location->id]);

        Livewire::test(ManageLocations::class)
            ->callAction(TestAction::make('delete')->table($location));

        $this->assertModelMissing($location);
        $this->assertNull($batch->fresh()->location_id);
    }

    public function test_a_batch_gets_a_location_of_the_household_only(): void
    {
        $cellar = Location::factory()->for($this->user->household)->create(['name' => 'Cellar']);
        $foreign = Location::factory()->create();

        Livewire::test(CreateBatch::class)
            ->fillForm(['product_id' => $this->product->id, 'quantity' => 2, 'location_id' => $foreign->id])
            ->call('create')
            ->assertHasFormErrors(['location_id']);

        Livewire::test(CreateBatch::class)
            ->fillForm(['product_id' => $this->product->id, 'quantity' => 2, 'location_id' => $cellar->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Batch::query()->sole()->location->is($cellar));
    }

    public function test_a_new_location_can_be_added_from_the_batch_form(): void
    {
        $batch = Batch::factory()->for($this->product)->create();
        // A location whose id equals the batch id must still count for the unique check.
        Location::factory()->for($this->user->household)->create(['id' => $batch->id, 'name' => 'Cellar']);

        Livewire::test(EditBatch::class, ['record' => $batch->getRouteKey()])
            ->callAction(TestAction::make('createOption')->schemaComponent('location_id'), ['name' => 'Cellar'])
            ->assertHasFormErrors(['name' => 'unique']);

        Livewire::test(EditBatch::class, ['record' => $batch->getRouteKey()])
            ->callAction(TestAction::make('createOption')->schemaComponent('location_id'), ['name' => 'Garage'])
            ->assertHasNoFormErrors()
            ->call('save')
            ->assertHasNoFormErrors();

        $garage = Location::query()->where('name', 'Garage')->sole();
        $this->assertSame($this->user->household_id, $garage->household_id);
        $this->assertTrue($batch->fresh()->location->is($garage));
    }

    public function test_the_batch_list_shows_and_filters_by_location(): void
    {
        $cellar = Location::factory()->for($this->user->household)->create(['name' => 'Cellar']);
        $inCellar = Batch::factory()->for($this->product)->create(['location_id' => $cellar->id, 'quantity' => 4]);
        $elsewhere = Batch::factory()->for($this->product)->create(['location_id' => null]);

        $this->assertSame('4 × 500 g · Cellar', BatchResource::details($inCellar->fresh()));

        Livewire::test(ListBatches::class)
            ->filterTable('location_id', $cellar->id)
            ->assertCanSeeTableRecords([$inCellar])
            ->assertCanNotSeeTableRecords([$elsewhere]);
    }
}
