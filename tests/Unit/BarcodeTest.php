<?php

namespace Tests\Unit;

use App\Support\Barcode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BarcodeTest extends TestCase
{
    /** @return array<string, array{?string, ?string}> */
    public static function codes(): array
    {
        return [
            'EAN-13 stays' => ['4006381333931', '4006381333931'],
            'UPC-A gets a leading zero' => ['036000291452', '0036000291452'],
            'EAN-8 is padded' => ['96385074', '0000096385074'],
            'GTIN-14 loses its leading zero' => ['04006381333931', '4006381333931'],
            'spaces and dashes are ignored' => ['4 006381-333931', '4006381333931'],
            'GTIN-14 with packaging indicator is rejected' => ['14006381333938', null],
            'too short' => ['1234567', null],
            'letters' => ['40063813339a1', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('codes')]
    public function test_barcodes_are_normalized_to_13_digits(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, Barcode::normalize($input));
    }
}
