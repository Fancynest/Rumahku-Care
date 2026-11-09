<?php

namespace Tests\Unit;

use App\Models\HomeAsset;
use App\Models\MaintenanceSchedule;
use App\Models\Vehicle;
use App\Services\ScheduleStatus;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class ScheduleStatusTest extends TestCase
{
    private ScheduleStatus $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScheduleStatus;
    }

    public function test_date_threshold_terlambat_when_past_or_today(): void
    {
        $scheduleToday = new MaintenanceSchedule([
            'trigger_type' => 'interval_hari',
            'next_due_date' => Carbon::today()->toDateString(),
        ]);
        $this->assertSame(ScheduleStatus::TERLAMBAT, $this->service->determine($scheduleToday));

        $schedulePast = new MaintenanceSchedule([
            'trigger_type' => 'tanggal',
            'next_due_date' => Carbon::yesterday()->toDateString(),
        ]);
        $this->assertSame(ScheduleStatus::TERLAMBAT, $this->service->determine($schedulePast));
    }

    public function test_date_threshold_mendesak_within_7_days(): void
    {
        $schedule = new MaintenanceSchedule([
            'trigger_type' => 'interval_hari',
            'next_due_date' => Carbon::today()->addDays(5)->toDateString(),
        ]);
        $this->assertSame(ScheduleStatus::MENDESAK, $this->service->determine($schedule));
    }

    public function test_date_threshold_perhatian_within_30_days(): void
    {
        $schedule = new MaintenanceSchedule([
            'trigger_type' => 'interval_hari',
            'next_due_date' => Carbon::today()->addDays(20)->toDateString(),
        ]);
        $this->assertSame(ScheduleStatus::PERHATIAN, $this->service->determine($schedule));
    }

    public function test_date_threshold_normal_beyond_30_days(): void
    {
        $schedule = new MaintenanceSchedule([
            'trigger_type' => 'interval_hari',
            'next_due_date' => Carbon::today()->addDays(45)->toDateString(),
        ]);
        $this->assertSame(ScheduleStatus::NORMAL, $this->service->determine($schedule));
    }

    public function test_km_threshold_terlambat_when_diff_less_equal_zero(): void
    {
        $vehicle = new Vehicle(['odometer' => 10000]);
        $schedule = new MaintenanceSchedule([
            'trigger_type' => 'kilometer',
            'next_due_km' => 10000,
        ]);
        $schedule->setRelation('subject', $vehicle);
        $schedule->subject_type = Vehicle::class;

        $this->assertSame(ScheduleStatus::TERLAMBAT, $this->service->determine($schedule));
    }

    public function test_km_threshold_mendesak_within_200_km(): void
    {
        $vehicle = new Vehicle(['odometer' => 10000]);
        $schedule = new MaintenanceSchedule([
            'trigger_type' => 'kilometer',
            'next_due_km' => 10180,
        ]);
        $schedule->setRelation('subject', $vehicle);
        $schedule->subject_type = Vehicle::class;

        $this->assertSame(ScheduleStatus::MENDESAK, $this->service->determine($schedule));
    }

    public function test_km_threshold_perhatian_within_500_km(): void
    {
        $vehicle = new Vehicle(['odometer' => 10000]);
        $schedule = new MaintenanceSchedule([
            'trigger_type' => 'kilometer',
            'next_due_km' => 10450,
        ]);
        $schedule->setRelation('subject', $vehicle);
        $schedule->subject_type = Vehicle::class;

        $this->assertSame(ScheduleStatus::PERHATIAN, $this->service->determine($schedule));
    }

    public function test_km_threshold_normal_beyond_500_km(): void
    {
        $vehicle = new Vehicle(['odometer' => 10000]);
        $schedule = new MaintenanceSchedule([
            'trigger_type' => 'kilometer',
            'next_due_km' => 11000,
        ]);
        $schedule->setRelation('subject', $vehicle);
        $schedule->subject_type = Vehicle::class;

        $this->assertSame(ScheduleStatus::NORMAL, $this->service->determine($schedule));
    }

    public function test_km_trigger_throws_exception_for_non_vehicle_subject(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $homeAsset = new HomeAsset(['name' => 'Pompa']);
        $schedule = new MaintenanceSchedule([
            'trigger_type' => 'kilometer',
            'next_due_km' => 1000,
        ]);
        $schedule->setRelation('subject', $homeAsset);
        $schedule->subject_type = HomeAsset::class;

        $this->service->determine($schedule);
    }

    public function test_priority_darurat_does_not_change_status(): void
    {
        $scheduleNormal = new MaintenanceSchedule([
            'trigger_type' => 'interval_hari',
            'next_due_date' => Carbon::today()->addDays(60)->toDateString(),
            'priority' => 'darurat',
        ]);
        $this->assertSame(ScheduleStatus::NORMAL, $this->service->determine($scheduleNormal));

        $schedulePerhatian = new MaintenanceSchedule([
            'trigger_type' => 'interval_hari',
            'next_due_date' => Carbon::today()->addDays(20)->toDateString(),
            'priority' => 'darurat',
        ]);
        $this->assertSame(ScheduleStatus::PERHATIAN, $this->service->determine($schedulePerhatian));

        $scheduleTerlambat = new MaintenanceSchedule([
            'trigger_type' => 'interval_hari',
            'next_due_date' => Carbon::today()->subDays(2)->toDateString(),
            'priority' => 'darurat',
        ]);
        $this->assertSame(ScheduleStatus::TERLAMBAT, $this->service->determine($scheduleTerlambat));
    }
}
