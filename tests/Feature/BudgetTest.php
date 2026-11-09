<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_user_can_set_and_update_budgets(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/finance/budgets', [
            'budgets' => [
                'total' => '5.000.000',
                'servis' => '1.000.000',
                'bbm' => '1.500.000',
                'perbaikan' => '500.000',
                'dokumen' => '',
            ],
        ]);

        $response->assertRedirect('/finance?month='.date('Y-m'));

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category' => null,
            'monthly_amount' => 5000000,
        ]);
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category' => 'servis',
            'monthly_amount' => 1000000,
        ]);
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category' => 'bbm',
            'monthly_amount' => 1500000,
        ]);
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category' => 'perbaikan',
            'monthly_amount' => 500000,
        ]);
        $this->assertDatabaseMissing('budgets', [
            'user_id' => $this->user->id,
            'category' => 'dokumen',
        ]);

        // Update: Ubah servis jadi 1.200.000 dan hapus bbm (kosongkan)
        $updateResponse = $this->post('/finance/budgets', [
            'budgets' => [
                'total' => '5.000.000',
                'servis' => '1.200.000',
                'bbm' => '0',
                'perbaikan' => '500.000',
                'dokumen' => '',
            ],
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category' => 'servis',
            'monthly_amount' => 1200000,
        ]);
        $this->assertDatabaseMissing('budgets', [
            'user_id' => $this->user->id,
            'category' => 'bbm',
        ]);
    }

    public function test_budget_unique_constraint_per_user_and_category(): void
    {
        Budget::create([
            'user_id' => $this->user->id,
            'category' => 'servis',
            'monthly_amount' => 1000000,
        ]);

        $this->expectException(QueryException::class);

        Budget::create([
            'user_id' => $this->user->id,
            'category' => 'servis',
            'monthly_amount' => 1500000,
        ]);
    }

    public function test_budget_status_thresholds(): void
    {
        $budget = new Budget([
            'user_id' => $this->user->id,
            'category' => 'bbm',
            'monthly_amount' => 1000000,
        ]);

        // 1. Aman: < 80%
        $progressSafe = $budget->calculateProgress(750000);
        $this->assertSame('aman', $progressSafe['status']);
        $this->assertSame('Aman', $progressSafe['label']);
        $this->assertSame('chip-success', $progressSafe['chip_class']);
        $this->assertEquals(75.0, $progressSafe['percentage']);

        // 2. Hati-hati: 80% - 99.9%
        $progressWarning = $budget->calculateProgress(850000);
        $this->assertSame('hati_hati', $progressWarning['status']);
        $this->assertSame('Hati-hati', $progressWarning['label']);
        $this->assertSame('chip-warning', $progressWarning['chip_class']);
        $this->assertEquals(85.0, $progressWarning['percentage']);

        // 3. Melebihi: >= 100%
        $progressOver = $budget->calculateProgress(1000000);
        $this->assertSame('melebihi', $progressOver['status']);
        $this->assertSame('Melebihi', $progressOver['label']);
        $this->assertSame('chip-urgent', $progressOver['chip_class']);
        $this->assertEquals(100.0, $progressOver['percentage']);

        $progressWayOver = $budget->calculateProgress(1350000);
        $this->assertSame('melebihi', $progressWayOver['status']);
        $this->assertEquals(135.0, $progressWayOver['percentage']);
    }

    public function test_finance_page_shows_budget_progress_with_real_expenses(): void
    {
        $this->actingAs($this->user);

        $vehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'plate' => 'B 3333 BDG',
            'odometer' => 10000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        // Set Total budget = 1,000,000 and BBM budget = 300,000
        Budget::create([
            'user_id' => $this->user->id,
            'category' => null,
            'monthly_amount' => 1000000,
        ]);
        Budget::create([
            'user_id' => $this->user->id,
            'category' => 'bbm',
            'monthly_amount' => 300000,
        ]);

        // Record BBM expense = 250,000 (83.3% of 300,000 -> Hati-hati)
        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $vehicle->id,
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => 10300,
            'liters' => 25,
            'total_cost' => 250000,
            'is_full_tank' => true,
        ]);

        $response = $this->get('/finance');
        $response->assertStatus(200);

        // Assert budget progress appears
        $response->assertSee('Anggaran Total (Semua Kategori)');
        $response->assertSee('Hati-hati');
        $response->assertSee('83.3% terpakai');
        $response->assertSee('Rp 250.000');
    }

    public function test_user_cannot_see_other_user_budgets(): void
    {
        $otherUser = User::factory()->create();
        Budget::create([
            'user_id' => $otherUser->id,
            'category' => null,
            'monthly_amount' => 10000000,
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/finance');
        $response->assertStatus(200);

        $response->assertSee('Belum ada anggaran yang diatur');
        $response->assertDontSee('10.000.000');
    }
}
