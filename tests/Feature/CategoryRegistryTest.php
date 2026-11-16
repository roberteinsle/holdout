<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Registries\CategoryDefinition;
use App\Registries\CategoryRegistry;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_supply_module_registers_the_ble_categories(): void
    {
        $categories = app(CategoryRegistry::class)->all();

        $this->assertSame(['water', 'grains', 'vegetables', 'fruit', 'dairy', 'protein', 'fats', 'other'], $categories->keys()->all());
        $this->assertFalse($categories['other']->hasTarget());
        $this->assertTrue($categories['fats']->hasTarget());

        // Per person and day, BLE stock calculator (2,200 kcal).
        $expected = ['water' => 2.0, 'grains' => 0.33, 'vegetables' => 0.40, 'fruit' => 0.25, 'dairy' => 0.25, 'protein' => 0.12, 'fats' => 0.033];
        foreach ($expected as $key => $perAdult) {
            $this->assertSame($perAdult, $categories[$key]->perAdultDay, $key);
            $this->assertNotNull($categories[$key]->source, $key);
        }

        // Children drink less (BLE); for food they count like adults.
        $this->assertSame(1.5, $categories['water']->perChild());
        $this->assertSame(0.33, $categories['grains']->perChild());
        $this->assertTrue($categories['water']->isWater);
        $this->assertSame('l', $categories['water']->unit);
        $this->assertSame('supply', app(CategoryRegistry::class)->moduleOf('grains'));
    }

    public function test_a_category_key_can_only_be_registered_once(): void
    {
        $this->expectException(\LogicException::class);

        app(CategoryRegistry::class)->register('other', new CategoryDefinition('water', 'Water', 'l', 1.0));
    }

    public function test_units_are_kg_or_l(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CategoryDefinition('x', 'X', 'pieces', 1.0);
    }

    public function test_the_seeder_syncs_the_registry_into_the_database_and_is_idempotent(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertSame(8, Category::query()->count());

        $water = Category::query()->where('key', 'water')->sole();
        $this->assertSame('supply', $water->module);
        $this->assertSame(2.0, $water->need_per_adult_day);
        $this->assertSame(1.5, $water->need_per_child_day);
        $this->assertTrue($water->is_water);

        app()->setLocale('de');
        $this->assertSame('Getränke', $water->label());
    }

    public function test_every_category_name_has_a_german_translation(): void
    {
        $german = json_decode(file_get_contents(lang_path('de.json')), true);

        foreach (app(CategoryRegistry::class)->all() as $category) {
            $this->assertArrayHasKey($category->name, $german, $category->key);
        }
    }
}
