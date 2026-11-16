<?php

namespace App\Modules\Equipment\Support;

use App\Models\Household;
use App\Modules\Equipment\Enums\EquipmentArea;
use App\Modules\Equipment\Models\EquipmentItem;

/**
 * Suggested items per area, following the BBK guide "Vorsorgen für Krisen und
 * Katastrophen" (Federal Office of Civil Protection and Disaster Assistance):
 * https://www.bbk.bund.de/DE/Warnung-Vorsorge/Vorsorge/vorsorge_node.html
 *
 * key => [English name (translated when added), check interval in months or null].
 */
final class Suggestions
{
    /** @return array<string, array<string, array{0: string, 1: ?int}>> area value => items */
    public static function all(): array
    {
        return [
            EquipmentArea::Pharmacy->value => [
                'personal_medication' => ['Personal medication', 6],
                'skin_disinfectant' => ['Skin disinfectant', 12],
                'wound_disinfectant' => ['Wound disinfectant', 12],
                'cold_remedies' => ['Cold remedies', 12],
                'thermometer' => ['Fever thermometer', 12],
                'diarrhoea_remedy' => ['Anti-diarrhoea medication', 12],
                'insect_sunburn_ointment' => ['Insect bite and sunburn ointment', 12],
                'splinter_tweezers' => ['Splinter tweezers', null],
                'bandages' => ['Bandages and dressings', 12],
                'first_aid_kit' => ['First aid kit', 12],
            ],
            EquipmentArea::Power->value => [
                'radio' => ['Battery or crank radio', 6],
                'torch' => ['Torch', 6],
                'spare_batteries' => ['Spare batteries', 12],
                'power_bank' => ['Power bank', 3],
                'candles' => ['Candles', null],
                'tea_lights' => ['Tea lights', null],
                'matches_lighter' => ['Matches or lighter', 12],
                'camping_stove' => ['Camping stove with fuel', 12],
                'blankets' => ['Warm blankets', null],
                'sleeping_bags' => ['Sleeping bags', null],
            ],
            EquipmentArea::Hygiene->value => [
                'soap' => ['Soap', null],
                'toothpaste' => ['Toothpaste', null],
                'toilet_paper' => ['Toilet paper', null],
                'rubbish_bags' => ['Rubbish bags', null],
                'disinfectant' => ['Disinfectant', 12],
                'household_gloves' => ['Household gloves', null],
                'toilet_bucket' => ['Emergency toilet bucket', null],
            ],
            EquipmentArea::Documents->value => [
                'document_folder' => ['Waterproof document folder', 12],
                'ids' => ['Identity cards and passports', 12],
                'certificates_civil' => ['Birth and marriage certificates', null],
                'insurance' => ['Insurance policies', 12],
                'certificates_education' => ['School and work certificates', null],
                'will' => ['Will', null],
                'power_of_attorney' => ['Lasting power of attorney', null],
            ],
            EquipmentArea::Cash->value => [
                'cash' => ['Cash in small notes and coins', 12],
            ],
            EquipmentArea::GrabBag->value => [
                'backpack' => ['Emergency backpack', 12],
                'bag_medication' => ['Medication for the grab bag', 6],
                'bag_documents' => ['Document folder (copies)', 12],
                'bag_clothing' => ['Clothing', 12],
                'bag_torch' => ['Torch for the grab bag', 6],
                'bag_radio' => ['Radio for the grab bag', 6],
                'bag_sleeping_bag' => ['Sleeping bag for the grab bag', null],
                'bag_water' => ['Water bottle', 12],
                'head_covering' => ['Head covering', null],
                'helmet' => ['Protective helmet', null],
                'mask' => ['Protective mask', 12],
                'respiratory_protection' => ['Makeshift respiratory protection', null],
                'work_gloves' => ['Work gloves', null],
            ],
            EquipmentArea::Fire->value => [
                'smoke_detectors' => ['Smoke detectors', 12],
                'fire_extinguisher' => ['Fire extinguisher', 24],
                'fire_blanket' => ['Fire blanket', null],
            ],
            EquipmentArea::Information->value => [
                'warning_app' => ['Warning app (e.g. NINA) installed', 12],
                'emergency_numbers' => ['Emergency numbers on paper', 12],
            ],
            EquipmentArea::Other->value => [
                'dishes' => ['Dishes and cutlery', null],
            ],
        ];
    }

    /** @return list<string> all English names, for translation checks */
    public static function names(): array
    {
        return array_values(array_merge(...array_map(
            fn (array $items): array => array_column($items, 0),
            array_values(self::all()),
        )));
    }

    /**
     * Add the suggested items of the given areas that the household does not have
     * yet, marked as missing. Returns how many were added.
     *
     * @param  list<EquipmentArea>  $areas
     */
    public static function addTo(Household $household, array $areas): int
    {
        $existing = EquipmentItem::query()
            ->where('household_id', $household->id)
            ->whereNotNull('suggestion_key')
            ->pluck('suggestion_key')
            ->all();

        $added = 0;

        foreach ($areas as $area) {
            foreach (self::all()[$area->value] ?? [] as $key => [$name, $interval]) {
                if (in_array($key, $existing, true)) {
                    continue;
                }

                $item = new EquipmentItem([
                    'area' => $area,
                    'name' => __($name),
                    'available' => false,
                    'check_interval_months' => $interval,
                ]);
                $item->household_id = $household->id;
                $item->suggestion_key = $key;
                $item->save();
                $added++;
            }
        }

        return $added;
    }
}
