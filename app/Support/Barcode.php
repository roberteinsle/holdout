<?php

namespace App\Support;

/**
 * Barcodes are stored with 13 digits (EAN-13): UPC-A (12) and EAN-8 get leading
 * zeros, a GTIN-14 with a leading zero loses it. Spaces and dashes are ignored.
 */
final class Barcode
{
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/[\s-]/', '', (string) $input);

        if ($digits === '' || ! preg_match('/^\d{8,14}$/', $digits)) {
            return null;
        }

        if (strlen($digits) === 14) {
            if ($digits[0] !== '0') {
                return null;
            }

            $digits = substr($digits, 1);
        }

        return str_pad($digits, 13, '0', STR_PAD_LEFT);
    }

    /** The barcode as typed (digits only), for sources that know the short form. */
    public static function digits(?string $input): string
    {
        return (string) preg_replace('/\D/', '', (string) $input);
    }
}
