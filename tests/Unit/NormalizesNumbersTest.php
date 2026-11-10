<?php

namespace Tests\Unit;

use App\Http\Controllers\Concerns\NormalizesNumbers;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class NormalizesNumbersTest extends TestCase
{
    private object $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new class
        {
            use NormalizesNumbers;
        };
    }

    public function test_normalizes_cost_and_odometer_integer_rules(): void
    {
        // Koma diikuti tepat 3 digit di akhir tanpa koma lain = pemisah ribuan
        $this->assertSame(52000, $this->normalizer->normalizeInteger('52,000'));
        $this->assertSame(10600, $this->normalizer->normalizeInteger('10,600'));
        $this->assertSame(1500, $this->normalizer->normalizeInteger('1,500'));
        $this->assertSame(52000, $this->normalizer->normalizeInteger('Rp 52,000'));

        // Bagian setelah koma = desimal atau akhiran jika bukan tepat 3 digit di akhir -> dibuang
        $this->assertSame(52000, $this->normalizer->normalizeInteger('52.000,00'));
        $this->assertSame(52000, $this->normalizer->normalizeInteger('Rp 52.000,-'));
        $this->assertSame(52000, $this->normalizer->normalizeInteger('52.000,50'));
        $this->assertSame(10600, $this->normalizer->normalizeInteger('10.600,00'));
        $this->assertSame(10800, $this->normalizer->normalizeInteger('10.800,-'));
        $this->assertSame(52, $this->normalizer->normalizeInteger('52,5'));
        $this->assertSame(52, $this->normalizer->normalizeInteger('52,00'));

        // Format standar polos atau dengan titik ribuan
        $this->assertSame(50000, $this->normalizer->normalizeInteger('50.000'));
        $this->assertSame(50000, $this->normalizer->normalizeInteger('50000'));
        $this->assertSame(0, $this->normalizer->normalizeInteger('0'));

        // Nilai kosong / null
        $this->assertNull($this->normalizer->normalizeInteger(null));
        $this->assertNull($this->normalizer->normalizeInteger(''));
        $this->assertNull($this->normalizer->normalizeInteger('   '));
    }

    public function test_normalizes_liters_decimal_rules(): void
    {
        // Koma dianggap desimal
        $this->assertSame(12.5, $this->normalizer->normalizeDecimal('12,5'));
        $this->assertSame(12.5, $this->normalizer->normalizeDecimal('12,50'));
        $this->assertSame(12.5, $this->normalizer->normalizeDecimal('12.5'));

        // Akhiran ,- dibuang
        $this->assertSame(10.0, $this->normalizer->normalizeDecimal('10,-'));
        $this->assertSame(10.0, $this->normalizer->normalizeDecimal('10, -'));

        // Dengan unit atau spasi
        $this->assertSame(14.25, $this->normalizer->normalizeDecimal('14.25 L'));
        $this->assertSame(14.25, $this->normalizer->normalizeDecimal('14,25 liter'));

        // Nilai kosong / null
        $this->assertNull($this->normalizer->normalizeDecimal(null));
        $this->assertNull($this->normalizer->normalizeDecimal(''));
        $this->assertNull($this->normalizer->normalizeDecimal('   '));
    }

    public function test_normalizes_request_numbers_merges_properly(): void
    {
        $request = Request::create('/test', 'POST', [
            'odometer' => '10,600',
            'total_cost' => 'Rp 52.000,00',
            'liters' => '12,5',
            'untouched' => 'string',
        ]);

        $this->normalizer->normalizeRequestNumbers(
            $request,
            integerFields: ['odometer', 'total_cost'],
            decimalFields: ['liters']
        );

        $this->assertSame(10600, $request->input('odometer'));
        $this->assertSame(52000, $request->input('total_cost'));
        $this->assertSame(12.5, $request->input('liters'));
        $this->assertSame('string', $request->input('untouched'));
    }
}
