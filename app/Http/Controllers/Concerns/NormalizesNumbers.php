<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait NormalizesNumbers
{
    /**
     * Normalisasi nilai biaya atau odometer (integer).
     * Aturan:
     * - Koma diikuti TEPAT 3 digit di akhir tanpa koma lain = pemisah ribuan ("52,000" -> 52000).
     * - Selain itu, bagian setelah koma = desimal/akhiran dan dibuang ("52.000,00" -> 52000, "Rp 52.000,-" -> 52000).
     */
    public function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $val = trim((string) $value);

        // Koma diikuti TEPAT 3 digit di akhir tanpa koma lain = pemisah ribuan ("52,000" -> 52000)
        if (substr_count($val, ',') === 1 && preg_match('/,\d{3}$/', $val)) {
            $val = str_replace(',', '', $val);
        } else {
            // Selain itu, bagian setelah koma = desimal/akhiran dan dibuang ("52.000,00" -> 52000, "Rp 52.000,-" -> 52000)
            $val = preg_replace('/,.*$/', '', $val);
        }

        $val = preg_replace('/[^0-9]/', '', $val);

        return $val !== '' ? (int) $val : null;
    }

    /**
     * Normalisasi nilai liter BBM (desimal float).
     * Aturan:
     * - Koma = desimal ("12,5" -> 12.5).
     * - Akhiran seperti ",-" dibuang.
     */
    public function normalizeDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $val = trim((string) $value);
        $val = preg_replace('/,\s*-\s*$/', '', $val);
        $val = str_replace(',', '.', $val);
        $val = preg_replace('/[^0-9.]/', '', $val);

        if ($val === '' || ! is_numeric($val)) {
            return null;
        }

        return (float) $val;
    }

    /**
     * Normalisasi field pada Request untuk integer / desimal.
     *
     * @param  list<string>  $integerFields
     * @param  list<string>  $decimalFields
     */
    public function normalizeRequestNumbers(Request $request, array $integerFields = [], array $decimalFields = []): void
    {
        foreach ($integerFields as $field) {
            if ($request->has($field)) {
                $val = $this->normalizeInteger($request->input($field));
                $request->merge([$field => $val]);
            }
        }

        foreach ($decimalFields as $field) {
            if ($request->has($field)) {
                $val = $this->normalizeDecimal($request->input($field));
                $request->merge([$field => $val]);
            }
        }
    }
}
