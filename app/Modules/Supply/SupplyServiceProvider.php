<?php

namespace App\Modules\Supply;

use App\Registries\CategoryDefinition;
use App\Registries\CategoryRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Supply module: food and water categories with the daily need per person.
 *
 * Values: Bundesanstalt für Landwirtschaft und Ernährung (BLE), stock calculator
 * at ernaehrungsvorsorge.de, per person and day at 2,200 kcal:
 * https://www.ernaehrungsvorsorge.de/private-vorsorge/notvorrat/vorratskalkulator/
 *
 * The BLE does not distinguish children for food, so children count like adults
 * there (the safe side). For drinks it does: adults 1.5 l for drinking plus
 * 0.5 l for cooking; children up to 12 years (not infants) drink 1 l on average.
 */
class SupplyServiceProvider extends ServiceProvider
{
    public const MODULE = 'supply';

    private const SOURCE = 'https://www.ernaehrungsvorsorge.de/private-vorsorge/notvorrat/vorratskalkulator/';

    public function register(): void
    {
        $this->app->afterResolving(CategoryRegistry::class, function (CategoryRegistry $registry): void {
            foreach ($this->categories() as $category) {
                $registry->register(self::MODULE, $category);
            }
        });
    }

    /** @return list<CategoryDefinition> */
    private function categories(): array
    {
        return [
            // 2.0 l: 1.5 l drinking + 0.5 l cooking. Children: 1.0 l drinking (BLE) + the same
            // 0.5 l for cooking, which the BLE counts per person (our addition, not a BLE figure).
            new CategoryDefinition('water', 'Drinks', 'l', perAdultDay: 2.0, perChildDay: 1.5, isWater: true, sort: 10, source: self::SOURCE),
            // Grain products, bread, potatoes: 0.33 kg
            new CategoryDefinition('grains', 'Grains, bread, potatoes', 'kg', perAdultDay: 0.33, sort: 20, source: self::SOURCE),
            // Vegetables, mushrooms (incl. pulses): 0.40 kg
            new CategoryDefinition('vegetables', 'Vegetables, pulses', 'kg', perAdultDay: 0.40, sort: 30, source: self::SOURCE),
            // Fruit (incl. nuts): 0.25 kg
            new CategoryDefinition('fruit', 'Fruit, nuts', 'kg', perAdultDay: 0.25, sort: 40, source: self::SOURCE),
            // Milk and dairy products: 0.25 kg
            new CategoryDefinition('dairy', 'Milk, dairy products', 'kg', perAdultDay: 0.25, sort: 50, source: self::SOURCE),
            // Eggs, meat, sausage, fish: 0.12 kg
            new CategoryDefinition('protein', 'Eggs, meat, fish', 'kg', perAdultDay: 0.12, sort: 60, source: self::SOURCE),
            // Fats, oil: 33 g
            new CategoryDefinition('fats', 'Fats, oil', 'kg', perAdultDay: 0.033, sort: 70, source: self::SOURCE),
            // Everything else in the supply (e.g. spices, sweets, baby food): no daily need, no target.
            new CategoryDefinition('other', 'Other', 'kg', perAdultDay: 0.0, sort: 900),
        ];
    }
}
