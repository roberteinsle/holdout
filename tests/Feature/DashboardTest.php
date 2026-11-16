<?php

namespace Tests\Feature;

use App\Enums\MemberType;
use App\Filament\Widgets\SupplyStatusWidget;
use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Enums\PackageUnit;
use App\Modules\Inventory\Filament\Widgets\EatSoonWidget;
use App\Modules\Inventory\Filament\Widgets\QuickActionsWidget;
use App\Modules\Inventory\Filament\Widgets\RecentActivityWidget;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Movement;
use App\Modules\Inventory\Models\Product;
use App\Registries\DashboardRegistry;
use Database\Seeders\CategorySeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-16 10:00:00');
        $this->seed(CategorySeeder::class);
        $household = Household::factory()->create(['target_days' => 10, 'eat_soon_days' => 30]);
        $household->setMemberCount(MemberType::Adult, 1);
        $this->user = User::factory()->for($household)->create(['name' => 'Alex']);
        $this->actingAs($this->user);
        $this->product = Product::factory()->for($household)->create([
            'name' => 'Water',
            'category_id' => Category::query()->where('key', 'water')->value('id'),
            'package_value' => 1.5,
            'package_unit' => PackageUnit::Litre,
        ]);
    }

    public function test_core_and_modules_register_their_widgets_through_the_dashboard_registry(): void
    {
        $widgets = app(DashboardRegistry::class)->widgets();

        foreach ([SupplyStatusWidget::class, QuickActionsWidget::class, EatSoonWidget::class, RecentActivityWidget::class] as $widget) {
            $this->assertContains($widget, $widgets);
        }

        $this->get('/')->assertOk()->assertSeeLivewire(QuickActionsWidget::class)->assertSeeLivewire(EatSoonWidget::class);
    }

    public function test_quick_actions_lead_to_scanning_and_adding_a_batch(): void
    {
        Livewire::test(QuickActionsWidget::class)
            ->assertSee('Scan')
            ->assertSee('Add batch')
            ->assertSee('/scan')
            ->assertSee('/batches/create');
    }

    public function test_the_supply_widget_shows_the_days_of_supply(): void
    {
        Batch::factory()->for($this->product)->create(['quantity' => 14]); // 21 l = 10 days of drinks

        Livewire::test(SupplyStatusWidget::class)
            ->assertSee('Your supply lasts 0 of 10 days.')
            ->assertSee('Runs out first: Grains, bread, potatoes')
            ->assertSeeInOrder(['Drinks', '100 % · 10 days']);
    }

    public function test_eat_soon_lists_expired_and_soon_expiring_batches_earliest_first(): void
    {
        $expired = Batch::factory()->for($this->product)->create(['best_before' => '2026-11-01']);
        $soon = Batch::factory()->for($this->product)->create(['best_before' => '2026-12-10']);
        $later = Batch::factory()->for($this->product)->create(['best_before' => '2027-06-01']);
        $undated = Batch::factory()->for($this->product)->create(['best_before' => null]);
        $foreign = Batch::factory()->create(['best_before' => '2026-11-20']);

        Livewire::test(EatSoonWidget::class)
            ->assertCanSeeTableRecords([$expired, $soon], inOrder: true)
            ->assertCanNotSeeTableRecords([$later, $undated, $foreign]);
    }

    public function test_packages_can_be_taken_out_from_the_dashboard(): void
    {
        $batch = Batch::factory()->for($this->product)->create(['quantity' => 3, 'best_before' => '2026-11-20']);

        Livewire::test(EatSoonWidget::class)
            ->callAction(TestAction::make('takeOut')->table($batch), ['quantity' => 1, 'type' => MovementType::Consumed->value])
            ->assertHasNoFormErrors();

        $this->assertSame(2, $batch->fresh()->quantity);
    }

    public function test_recent_activity_shows_the_latest_five_entries_of_the_household(): void
    {
        $entries = collect(range(1, 7))->map(function (int $i): Movement {
            $this->travelTo(now()->addMinute());

            return Movement::factory()->for($this->product)->create(['user_id' => $this->user->id, 'quantity' => -$i]);
        });
        $foreign = Movement::factory()->create();

        Livewire::test(RecentActivityWidget::class)
            ->assertCanSeeTableRecords($entries->reverse()->take(5)->values()->all(), inOrder: true)
            ->assertCanNotSeeTableRecords([$entries[0], $entries[1], $foreign])
            ->assertSee('−7 × 1.5 l · Alex');
    }

    public function test_an_empty_dashboard_still_works(): void
    {
        Livewire::test(EatSoonWidget::class)->assertSee('Nothing to eat soon');
        Livewire::test(RecentActivityWidget::class)->assertSee('Nothing has happened yet');
    }
}
