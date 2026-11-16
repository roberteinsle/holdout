<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use App\Modules\Equipment\Enums\CheckResult;
use App\Modules\Equipment\Enums\EquipmentArea;
use App\Modules\Equipment\Enums\EquipmentStatus;
use App\Modules\Equipment\Filament\Resources\Equipment\EquipmentResource;
use App\Modules\Equipment\Filament\Resources\Equipment\Pages\CreateEquipment;
use App\Modules\Equipment\Filament\Resources\Equipment\Pages\EditEquipment;
use App\Modules\Equipment\Filament\Resources\Equipment\Pages\ListEquipment;
use App\Modules\Equipment\Filament\Resources\Equipment\RelationManagers\ChecksRelationManager;
use App\Modules\Equipment\Models\EquipmentCheck;
use App\Modules\Equipment\Models\EquipmentItem;
use App\Modules\Equipment\Support\Suggestions;
use App\Registries\NavigationRegistry;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->for(Household::factory())->create();
        $this->actingAs($this->user);
    }

    private function item(array $attributes = []): EquipmentItem
    {
        return EquipmentItem::factory()->for($this->user->household)->create($attributes);
    }

    public function test_the_module_registers_its_page_through_the_navigation_registry(): void
    {
        $this->assertContains(EquipmentResource::class, app(NavigationRegistry::class)->resources());
        $this->get(EquipmentResource::getUrl('index'))->assertOk()->assertSee('No equipment yet');
    }

    public function test_suggestions_are_added_as_missing_and_never_twice(): void
    {
        Livewire::test(ListEquipment::class)
            ->callAction('suggestions', ['areas' => [EquipmentArea::Pharmacy->value, EquipmentArea::Fire->value]])
            ->assertNotified();

        $expected = count(Suggestions::all()['pharmacy']) + count(Suggestions::all()['fire']);
        $this->assertSame($expected, EquipmentItem::query()->count());
        $this->assertSame(0, EquipmentItem::query()->where('available', true)->count());
        $this->assertSame(EquipmentStatus::Missing, EquipmentItem::query()->first()->status());

        // A second run adds nothing, also when an item was renamed.
        EquipmentItem::query()->first()->update(['name' => 'Renamed']);
        Livewire::test(ListEquipment::class)
            ->callAction('suggestions', ['areas' => [EquipmentArea::Pharmacy->value, EquipmentArea::Fire->value]]);
        $this->assertSame($expected, EquipmentItem::query()->count());
    }

    public function test_suggestions_are_named_in_the_persons_language(): void
    {
        app()->setLocale('de');
        Suggestions::addTo($this->user->household, [EquipmentArea::Fire]);

        $this->assertTrue(EquipmentItem::query()->where('name', 'Feuerlöscher')->exists());
        $this->assertSame(24, EquipmentItem::query()->where('suggestion_key', 'fire_extinguisher')->value('check_interval_months'));
    }

    public function test_an_item_is_created_for_the_household_and_validated(): void
    {
        Livewire::test(CreateEquipment::class)
            ->fillForm(['name' => 'Camping stove', 'area' => EquipmentArea::Power->value, 'quantity' => 1, 'expires_on' => '2028-05-31', 'check_interval_months' => 12, 'location' => 'Cellar'])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = EquipmentItem::query()->sole();
        $this->assertSame($this->user->household_id, $item->household_id);
        $this->assertTrue($item->available);
        $this->assertSame('2028-05-31', $item->expires_on->toDateString());
        $this->assertNotNull($item->next_check_on);

        Livewire::test(CreateEquipment::class)
            ->fillForm(['name' => '', 'area' => 'garden', 'quantity' => -1, 'check_interval_months' => 0])
            ->call('create')
            ->assertHasFormErrors(['name', 'area', 'quantity', 'check_interval_months']);
    }

    public function test_status_follows_availability_expiry_attention_and_checks(): void
    {
        $this->travelTo('2026-11-17 10:00');

        $this->assertSame(EquipmentStatus::Missing, $this->item(['available' => false])->status());
        $this->assertSame(EquipmentStatus::Expired, $this->item(['expires_on' => '2026-11-01'])->status());
        $this->assertSame(EquipmentStatus::ExpiresSoon, $this->item(['expires_on' => '2026-12-20'])->status());
        $this->assertSame(EquipmentStatus::Ok, $this->item(['expires_on' => '2027-12-01'])->status());

        $torch = $this->item(['check_interval_months' => 6]);
        $this->assertSame(EquipmentStatus::Ok, $torch->status());
        $this->travelTo('2027-05-18 10:00');
        $this->assertSame(EquipmentStatus::CheckDue, $torch->fresh()->status());
    }

    public function test_each_item_matches_exactly_the_status_filter_of_its_status(): void
    {
        $this->travelTo('2026-11-17 10:00');
        $items = [
            $this->item(['available' => false]),
            $this->item(['expires_on' => '2026-11-01']),
            $this->item(['expires_on' => '2026-12-20']),
            $this->item(['expires_on' => '2027-12-01']),
            $this->item(),
            tap($this->item(), fn ($i) => $i->forceFill(['attention' => true])->save()),
            tap($this->item(['check_interval_months' => 1]), fn ($i) => $i->forceFill(['last_checked_at' => '2026-01-01'])->save()),
        ];

        foreach ($items as $item) {
            $matches = collect(EquipmentStatus::cases())
                ->filter(fn (EquipmentStatus $status) => EquipmentItem::query()->whereKey($item->id)->withStatus($status)->exists())
                ->values()->all();

            $this->assertSame([$item->fresh()->status()], $matches, "Item {$item->id}");
        }
    }

    public function test_checking_records_the_log_and_updates_the_item(): void
    {
        $this->travelTo('2026-11-17 10:00');
        $stove = $this->item(['name' => 'Camping stove', 'available' => false, 'expires_on' => '2026-10-01', 'check_interval_months' => 12]);

        Livewire::test(ListEquipment::class)
            ->callAction(TestAction::make('check')->table($stove), ['result' => CheckResult::Replaced->value, 'expires_on' => '2029-10-01', 'note' => 'New gas cartridge'])
            ->assertNotified();

        $stove->refresh();
        $this->assertTrue($stove->available);
        $this->assertSame('2029-10-01', $stove->expires_on->toDateString());
        $this->assertSame('2027-11-17', $stove->next_check_on->toDateString());
        $this->assertSame(EquipmentStatus::Ok, $stove->status());

        $check = $stove->checks()->sole();
        $this->assertSame(CheckResult::Replaced, $check->result);
        $this->assertSame($this->user->id, $check->user_id);
        $this->assertSame('New gas cartridge', $check->note);
    }

    public function test_needs_attention_flags_the_item_until_a_later_check_is_ok(): void
    {
        $radio = $this->item(['name' => 'Radio']);

        $radio->recordCheck(CheckResult::NeedsAttention, $this->user, 'Batteries empty');
        $this->assertSame(EquipmentStatus::NeedsAttention, $radio->fresh()->status());

        $radio->recordCheck(CheckResult::Ok, $this->user);
        $this->assertSame(EquipmentStatus::Ok, $radio->fresh()->status());
        $this->assertSame(2, $radio->checks()->count());
    }

    public function test_the_list_is_grouped_searchable_filterable_and_per_household(): void
    {
        $stove = $this->item(['name' => 'Camping stove', 'area' => EquipmentArea::Power, 'location' => 'Cellar']);
        $kit = $this->item(['name' => 'First aid kit', 'area' => EquipmentArea::Pharmacy, 'available' => false]);
        $other = EquipmentItem::factory()->create(['name' => 'Neighbour stove']);

        Livewire::test(ListEquipment::class)
            ->assertCanSeeTableRecords([$stove, $kit])
            ->assertCanNotSeeTableRecords([$other])
            ->assertSee(['Home pharmacy', 'Power outage'])
            ->searchTable('cellar')
            ->assertCanSeeTableRecords([$stove])
            ->assertCanNotSeeTableRecords([$kit])
            ->searchTable('')
            ->filterTable('status', EquipmentStatus::Missing->value)
            ->assertCanSeeTableRecords([$kit])
            ->assertCanNotSeeTableRecords([$stove])
            ->resetTableFilters()
            ->filterTable('area', EquipmentArea::Power->value)
            ->assertCanSeeTableRecords([$stove])
            ->assertCanNotSeeTableRecords([$kit]);

        $this->get(EquipmentResource::getUrl('edit', ['record' => $other]))->assertNotFound();
    }

    public function test_several_items_can_be_checked_at_once(): void
    {
        $items = collect([$this->item(['available' => false]), $this->item()]);

        Livewire::test(ListEquipment::class)
            ->selectTableRecords($items->pluck('id')->all())
            ->callAction(TestAction::make('checkedOk')->table()->bulk());

        $items->each(fn ($item) => $this->assertSame(EquipmentStatus::Ok, $item->fresh()->status()));
        $this->assertSame(2, EquipmentCheck::query()->count());
    }

    public function test_the_edit_page_shows_the_check_log(): void
    {
        $item = $this->item(['name' => 'Torch']);
        $item->recordCheck(CheckResult::Ok, $this->user, 'Works');

        $this->get(EquipmentResource::getUrl('edit', ['record' => $item]))->assertOk();
        Livewire::test(EditEquipment::class, ['record' => $item->getRouteKey()])->callAction('check', ['result' => CheckResult::Ok->value]);
        $this->assertSame(2, $item->checks()->count());

        Livewire::test(ChecksRelationManager::class, ['ownerRecord' => $item, 'pageClass' => EditEquipment::class])
            ->assertSee('Check log')
            ->assertCanSeeTableRecords($item->checks()->get())
            ->assertSee(['Works', $this->user->name]);
    }

    public function test_the_printout_contains_the_equipment_checklist(): void
    {
        $this->item(['name' => 'Camping stove', 'area' => EquipmentArea::Power, 'expires_on' => '2028-05-31']);
        $this->item(['name' => 'Fire blanket', 'area' => EquipmentArea::Fire, 'available' => false]);

        $this->get(route('printout'))
            ->assertOk()
            ->assertSeeInOrder(['Equipment checklist', 'Power outage', '☒ Camping stove', 'Fire safety', '☐ Fire blanket']);
    }
}
