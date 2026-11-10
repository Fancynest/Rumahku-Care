<?php

namespace Tests\Feature;

use App\Models\DamageReport;
use App\Models\Document;
use App\Models\FuelLog;
use App\Models\HomeAsset;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ExpenseReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Vehicle $vehicle;

    protected HomeAsset $homeAsset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'plate' => 'B 7777 FIN',
            'odometer' => 10000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);
        $this->homeAsset = HomeAsset::create([
            'user_id' => $this->user->id,
            'name' => 'AC Ruang Tamu',
            'category' => 'pendingin',
            'room' => 'Ruang Tamu',
            'condition' => 'normal',
        ]);
    }

    public function test_user_can_view_finance_page_with_normalized_expenses(): void
    {
        $this->actingAs($this->user);

        // 1. Servis
        MaintenanceRecord::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'done_at' => Carbon::today()->toDateString(),
            'service_type' => 'Ganti Oli Mesin',
            'labor_cost' => 50000,
            'parts_cost' => 100000,
            'odometer' => 10000,
        ]);

        // 2. BBM
        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => 10250,
            'liters' => 20,
            'total_cost' => 200000,
            'is_full_tank' => true,
        ]);

        // 3. Perbaikan Kerusakan Selesai
        DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Pipa Bocor',
            'severity' => 'sedang',
            'status' => 'selesai',
            'reported_at' => Carbon::today()->subDays(2)->toDateString(),
            'resolved_at' => Carbon::today()->toDateString(),
            'repair_cost' => 350000,
        ]);

        // 4. Dokumen
        Document::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'pajak_tahunan',
            'title' => 'Pajak Tahunan STNK',
            'cost' => 500000,
            'issued_at' => Carbon::today()->toDateString(),
        ]);

        $response = $this->get('/finance');
        $response->assertStatus(200);

        // Total: 150k + 200k + 350k + 500k = 1,200,000
        $response->assertSee('Rp 1.200.000');
        $response->assertSee('Ganti Oli Mesin');
        $response->assertSee('BBM');
        $response->assertSee('Pipa Bocor');
        $response->assertSee('Pajak Tahunan STNK');
    }

    public function test_unresolved_damage_reports_or_zero_cost_are_excluded(): void
    {
        $this->actingAs($this->user);

        // Unresolved damage report
        DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => $this->vehicle->getMorphClass(),
            'reportable_id' => $this->vehicle->id,
            'title' => 'Lampu Pecah',
            'severity' => 'ringan',
            'status' => 'dalam_perbaikan',
            'reported_at' => Carbon::today()->toDateString(),
            'repair_cost' => 150000,
        ]);

        // Resolved damage report but cost is 0
        DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => $this->vehicle->getMorphClass(),
            'reportable_id' => $this->vehicle->id,
            'title' => 'Baut Kendor',
            'severity' => 'ringan',
            'status' => 'selesai',
            'reported_at' => Carbon::today()->toDateString(),
            'resolved_at' => Carbon::today()->toDateString(),
            'repair_cost' => 0,
        ]);

        $report = app(ExpenseReport::class);
        $expenses = $report->getAllExpenses($this->user);

        $this->assertCount(0, $expenses);
    }

    public function test_document_zero_cost_is_excluded_but_archived_with_cost_is_included(): void
    {
        $this->actingAs($this->user);

        // Document cost = 0 -> excluded
        Document::create([
            'user_id' => $this->user->id,
            'subject_type' => $this->vehicle->getMorphClass(),
            'subject_id' => $this->vehicle->id,
            'type' => 'manual_book',
            'title' => 'Buku Manual',
            'cost' => 0,
            'issued_at' => Carbon::today()->toDateString(),
        ]);

        // Document cost = 300,000 and archived -> included
        Document::create([
            'user_id' => $this->user->id,
            'subject_type' => $this->vehicle->getMorphClass(),
            'subject_id' => $this->vehicle->id,
            'type' => 'pajak_tahunan',
            'title' => 'Pajak STNK 2025',
            'cost' => 300000,
            'issued_at' => Carbon::today()->toDateString(),
            'archived_at' => Carbon::now(),
        ]);

        $report = app(ExpenseReport::class);
        $expenses = $report->getAllExpenses($this->user);

        $this->assertCount(1, $expenses);
        $this->assertSame(300000, $expenses->first()->amount);
    }

    public function test_month_boundary_in_jakarta_timezone(): void
    {
        $this->actingAs($this->user);

        // Record on October 31
        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => '2026-10-31',
            'odometer' => 10100,
            'liters' => 20,
            'total_cost' => 200000,
        ]);

        // Record on November 01
        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => '2026-11-01',
            'odometer' => 10400,
            'liters' => 25,
            'total_cost' => 250000,
        ]);

        $report = app(ExpenseReport::class);

        $novExpenses = $report->getFilteredItems($this->user, ['month' => '2026-11']);
        $this->assertCount(1, $novExpenses);
        $this->assertSame(250000, $novExpenses->first()->amount);

        $octExpenses = $report->getFilteredItems($this->user, ['month' => '2026-10']);
        $this->assertCount(1, $octExpenses);
        $this->assertSame(200000, $octExpenses->first()->amount);
    }

    public function test_six_month_trend_structure_and_values(): void
    {
        $this->actingAs($this->user);

        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => '2026-11-05',
            'odometer' => 10500,
            'liters' => 20,
            'total_cost' => 200000,
        ]);

        $report = app(ExpenseReport::class);
        $data = $report->getMonthlyReport($this->user, ['month' => '2026-11']);

        $this->assertCount(6, $data['six_month_trend']);
        $lastBar = end($data['six_month_trend']);
        $this->assertSame('2026-11', $lastBar['month']);
        $this->assertSame(200000, $lastBar['total']);
        $this->assertTrue($lastBar['is_selected']);
    }

    public function test_filtering_by_category_and_subject(): void
    {
        $this->actingAs($this->user);

        // BBM on vehicle
        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => 10200,
            'liters' => 20,
            'total_cost' => 200000,
        ]);

        // Perbaikan on home asset
        DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Remot Rusak',
            'severity' => 'ringan',
            'status' => 'selesai',
            'reported_at' => Carbon::today()->toDateString(),
            'resolved_at' => Carbon::today()->toDateString(),
            'repair_cost' => 75000,
        ]);

        $responseBBM = $this->get('/finance?category=bbm');
        $responseBBM->assertSee('BBM');
        $responseBBM->assertDontSee('Remot Rusak');

        $responseHome = $this->get("/finance?subject=home_asset:{$this->homeAsset->id}");
        $responseHome->assertSee('Remot Rusak');
        $responseHome->assertDontSee('BBM •');
    }

    public function test_csv_export_with_injection_protection_and_utf8_bom(): void
    {
        $this->actingAs($this->user);

        // Uji sanitasi sel yang diawali '=', '+', '-', '@', tab, atau carriage return
        $injectionTitles = [
            '=cmd|"/C calc"!A0',
            '+cmd|"/C calc"!A0',
            '-cmd|"/C calc"!A0',
            '@SUM(A1:A10)',
            "\tTAB_INJECTION",
            "\rCR_INJECTION",
        ];

        foreach ($injectionTitles as $idx => $injTitle) {
            DamageReport::create([
                'user_id' => $this->user->id,
                'reportable_type' => HomeAsset::class,
                'reportable_id' => $this->homeAsset->id,
                'title' => $injTitle,
                'severity' => 'sedang',
                'status' => 'selesai',
                'reported_at' => Carbon::today()->toDateString(),
                'resolved_at' => Carbon::today()->toDateString(),
                'repair_cost' => 10000 + $idx,
            ]);
        }

        $response = $this->get('/finance/export');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        $content = $response->streamedContent();

        // Must begin with UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Tiap karakter bahaya harus disanitasi dengan awalan petik tunggal (')
        $this->assertStringContainsString("'=cmd", $content);
        $this->assertStringContainsString("'+cmd", $content);
        $this->assertStringContainsString("'-cmd", $content);
        $this->assertStringContainsString("'@SUM", $content);
        $this->assertStringContainsString("'\tTAB_INJECTION", $content);
        $this->assertStringContainsString("'\rCR_INJECTION", $content);
    }

    public function test_user_a_cannot_see_user_b_expenses(): void
    {
        $userB = User::factory()->create();
        $vehicleB = Vehicle::create([
            'user_id' => $userB->id,
            'plate' => 'D 9999 SBD',
            'odometer' => 5000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        FuelLog::create([
            'user_id' => $userB->id,
            'vehicle_id' => $vehicleB->id,
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => 5200,
            'liters' => 30,
            'total_cost' => 300000,
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/finance');
        $response->assertStatus(200);
        $response->assertSee('Rp 0');
        $response->assertDontSee('D 9999 SBD');
    }

    public function test_finance_index_does_not_suffer_from_n_plus_one_queries(): void
    {
        // Buat 10 catatan pengeluaran berbagai jenis
        for ($i = 1; $i <= 10; $i++) {
            FuelLog::create([
                'user_id' => $this->user->id,
                'vehicle_id' => $this->vehicle->id,
                'filled_at' => Carbon::today()->subDays($i)->toDateString(),
                'odometer' => 10000 + ($i * 100),
                'liters' => 15,
                'total_cost' => 150000,
            ]);
        }

        $this->actingAs($this->user);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get('/finance');
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        // Memastikan jumlah query tetap terikat dan tidak bertambah per baris data
        $this->assertLessThanOrEqual(20, count($queries));
    }
}
