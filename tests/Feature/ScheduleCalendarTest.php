<?php

namespace Tests\Feature;

use App\Models\HomeAsset;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ScheduleCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleCalendarTest extends TestCase
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
            'plate' => 'B 1234 CAL',
            'odometer' => 15000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);
        $this->homeAsset = HomeAsset::create([
            'user_id' => $this->user->id,
            'name' => 'Pompa Air Utama',
            'category' => 'air',
            'room' => 'Halaman Belakang',
            'condition' => 'normal',
        ]);
    }

    public function test_calendar_page_can_be_rendered_with_default_month(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('schedules.calendar'));

        $response->assertOk();
        $response->assertViewIs('schedules.calendar');
        $response->assertSee('Kalender Agenda dan Jatuh Tempo');
        $response->assertSee(Carbon::today()->translatedFormat('F Y'));
    }

    public function test_active_date_schedule_appears_on_calendar_grid(): void
    {
        $this->actingAs($this->user);

        $targetDate = Carbon::today()->startOfMonth()->addDays(14)->toDateString();
        $schedule = $this->vehicle->schedules()->create([
            'user_id' => $this->user->id,
            'title' => 'Ganti Minyak Rem Terjadwal',
            'trigger_type' => 'tanggal',
            'interval_months' => 6,
            'next_due_date' => $targetDate,
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        $monthStr = Carbon::today()->format('Y-m');
        $response = $this->get(route('schedules.calendar', ['month' => $monthStr]));

        $response->assertOk();
        $response->assertSee('Ganti Minyak Rem Terjadwal');
    }

    public function test_inactive_date_schedule_does_not_appear(): void
    {
        $this->actingAs($this->user);

        $targetDate = Carbon::today()->startOfMonth()->addDays(10)->toDateString();
        $this->homeAsset->schedules()->create([
            'user_id' => $this->user->id,
            'title' => 'Jadwal Nonaktif Pembersihan',
            'trigger_type' => 'tanggal',
            'interval_months' => 3,
            'next_due_date' => $targetDate,
            'priority' => 'normal',
            'is_active' => false,
        ]);

        $monthStr = Carbon::today()->format('Y-m');
        $response = $this->get(route('schedules.calendar', ['month' => $monthStr]));

        $response->assertOk();
        $response->assertDontSee('Jadwal Nonaktif Pembersihan');
    }

    public function test_expiring_document_appears_on_calendar(): void
    {
        $this->actingAs($this->user);

        $targetDate = Carbon::today()->startOfMonth()->addDays(20)->toDateString();
        $this->vehicle->documents()->create([
            'user_id' => $this->user->id,
            'type' => 'stnk_pajak',
            'title' => 'Pajak STNK Tahunan',
            'expires_at' => $targetDate,
            'cost' => 500000,
        ]);

        $monthStr = Carbon::today()->format('Y-m');
        $response = $this->get(route('schedules.calendar', ['month' => $monthStr]));

        $response->assertOk();
        $response->assertSee('Pajak STNK Tahunan');
    }

    public function test_archived_document_does_not_appear(): void
    {
        $this->actingAs($this->user);

        $targetDate = Carbon::today()->startOfMonth()->addDays(5)->toDateString();
        $this->vehicle->documents()->create([
            'user_id' => $this->user->id,
            'type' => 'asuransi',
            'title' => 'Polis Asuransi Lama Arsip',
            'expires_at' => $targetDate,
            'archived_at' => Carbon::now(),
        ]);

        $monthStr = Carbon::today()->format('Y-m');
        $response = $this->get(route('schedules.calendar', ['month' => $monthStr]));

        $response->assertOk();
        $response->assertDontSee('Polis Asuransi Lama Arsip');
    }

    public function test_completed_service_record_appears_only_when_show_completed_is_true(): void
    {
        $this->actingAs($this->user);

        $doneDate = Carbon::today()->startOfMonth()->addDays(7)->toDateString();
        MaintenanceRecord::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'service_type' => 'Tune Up Rutin Rampung',
            'done_at' => $doneDate,
            'cost' => 350000,
            'odometer' => 15000,
        ]);

        $monthStr = Carbon::today()->format('Y-m');

        // Saat toggle off -> tidak muncul
        $responseOff = $this->get(route('schedules.calendar', ['month' => $monthStr, 'show_completed' => '0']));
        $responseOff->assertOk();
        $responseOff->assertDontSee('Tune Up Rutin Rampung');

        // Saat toggle on -> muncul
        $responseOn = $this->get(route('schedules.calendar', ['month' => $monthStr, 'show_completed' => '1']));
        $responseOn->assertOk();
        $responseOn->assertSee('Tune Up Rutin Rampung');
    }

    public function test_km_schedule_is_counted_and_not_in_date_grid(): void
    {
        $this->actingAs($this->user);

        $this->vehicle->schedules()->create([
            'user_id' => $this->user->id,
            'title' => 'Rotasi Ban 10000 KM',
            'trigger_type' => 'kilometer',
            'interval_km' => 10000,
            'next_due_km' => 20000,
            'priority' => 'perhatian',
            'is_active' => true,
        ]);

        $response = $this->get(route('schedules.calendar'));

        $response->assertOk();
        $response->assertSee('1 jadwal perawatan berbasis KM');
        $response->assertSee('Jadwal ini dipicu oleh jarak tempuh odometer');
    }

    public function test_data_isolation_between_users(): void
    {
        $otherUser = User::factory()->create();
        $otherVehicle = Vehicle::create([
            'user_id' => $otherUser->id,
            'plate' => 'D 9999 PRIV',
            'odometer' => 5000,
            'color_key' => 'putih',
            'fuel_type' => 'Bensin',
        ]);

        $targetDate = Carbon::today()->startOfMonth()->addDays(12)->toDateString();
        $otherVehicle->schedules()->create([
            'user_id' => $otherUser->id,
            'title' => 'Jadwal Rahasia User Lain',
            'trigger_type' => 'tanggal',
            'interval_months' => 1,
            'next_due_date' => $targetDate,
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('schedules.calendar', ['month' => Carbon::today()->format('Y-m')]));
        $response->assertOk();
        $response->assertDontSee('Jadwal Rahasia User Lain');
    }

    public function test_invalid_month_falls_back_to_current_month(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('schedules.calendar', ['month' => 'invalid-month-string']));
        $response->assertOk();
        $response->assertSee(Carbon::today()->translatedFormat('F Y'));

        $response2 = $this->get(route('schedules.calendar', ['month' => '2026-99']));
        $response2->assertOk();
        $response2->assertSee(Carbon::today()->translatedFormat('F Y'));
    }

    public function test_grid_starts_on_monday_and_handles_different_month_lengths(): void
    {
        /** @var ScheduleCalendar $calendar */
        $calendar = app(ScheduleCalendar::class);

        // Uji bulan 28 hari (Feb 2023 non-kabisat)
        $data28 = $calendar->build($this->user, '2023-02');
        $this->assertNotEmpty($data28['weeks']);
        // Hari pertama di baris pertama pekan harus selalu hari Senin (ISO 1)
        $this->assertEquals(1, $data28['weeks'][0][0]['date']->dayOfWeekIso);
        // Hari terakhir di baris terakhir pekan harus selalu hari Minggu (ISO 7)
        $lastWeek28 = end($data28['weeks']);
        $this->assertEquals(7, end($lastWeek28)['date']->dayOfWeekIso);

        // Uji bulan 29 hari (Feb 2024 kabisat)
        $data29 = $calendar->build($this->user, '2024-02');
        $this->assertEquals(1, $data29['weeks'][0][0]['date']->dayOfWeekIso);
        $lastWeek29 = end($data29['weeks']);
        $this->assertEquals(7, end($lastWeek29)['date']->dayOfWeekIso);

        // Uji bulan 30 hari (April 2026)
        $data30 = $calendar->build($this->user, '2026-04');
        $this->assertEquals(1, $data30['weeks'][0][0]['date']->dayOfWeekIso);
        $lastWeek30 = end($data30['weeks']);
        $this->assertEquals(7, end($lastWeek30)['date']->dayOfWeekIso);

        // Uji bulan 31 hari (Januari 2026)
        $data31 = $calendar->build($this->user, '2026-01');
        $this->assertEquals(1, $data31['weeks'][0][0]['date']->dayOfWeekIso);
        $lastWeek31 = end($data31['weeks']);
        $this->assertEquals(7, end($lastWeek31)['date']->dayOfWeekIso);
    }
}
