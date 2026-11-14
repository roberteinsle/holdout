<?php

namespace Tests\Feature;

use App\Support\Palette;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\ButtonComponent;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Colour roles and WCAG contrast from the design doc.
 */
class PaletteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FilamentColor::register(Palette::filament());
    }

    /** @return array<string, array{string, string, int, int}> */
    public static function filledButtons(): array
    {
        // colour => [light bg hex, light text hex, dark bg shade, dark text shade]
        return [
            'primary: white on olive-900' => ['primary', '#4A5342', '#FFFFFF', 400, 950],
            'success: white on olive-600' => ['success', '#616847', '#FFFFFF', 400, 950],
            'warning: ink on signal orange' => ['warning', '#FF860F', '#242529', 500, 950],
            'danger: white on alert-600' => ['danger', '#D91E00', '#FFFFFF', 600, 0],
        ];
    }

    #[DataProvider('filledButtons')]
    public function test_filled_buttons_use_the_design_colours(string $name, string $bg, string $text, int $darkBg, int $darkText): void
    {
        $palette = FilamentColor::getColor($name);
        $map = ButtonComponent::make()->getColorMap($palette);

        $this->assertSame(Color::convertToOklch($bg), $palette[$map['bg']]);
        $this->assertSame(
            Color::convertToOklch($text),
            $map['text'] === 0 ? Color::convertToOklch('#FFFFFF') : $palette[$map['text']],
        );
        $this->assertSame($darkBg, $map['dark:bg']);
        $this->assertSame($darkText, $map['dark:text']);

        $textColour = $map['text'] === 0 ? Color::convertToOklch('#FFFFFF') : $palette[$map['text']];
        $this->assertGreaterThanOrEqual(4.5, Color::calculateContrastRatio($palette[$map['bg']], $textColour));

        $darkTextColour = $map['dark:text'] === 0 ? Color::convertToOklch('#FFFFFF') : $palette[$map['dark:text']];
        $this->assertGreaterThanOrEqual(4.5, Color::calculateContrastRatio($palette[$map['dark:bg']], $darkTextColour));
    }

    public function test_muted_text_is_readable_on_white_and_on_dark_cards(): void
    {
        $gray = FilamentColor::getColor('gray');

        $this->assertGreaterThanOrEqual(4.5, Color::calculateContrastRatio($gray[500], Color::convertToOklch('#FFFFFF')));
        $this->assertGreaterThanOrEqual(4.5, Color::calculateContrastRatio($gray[400], $gray[900]));
    }
}
