<?php

namespace App\Services;

use App\Models\Document;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

class ScheduleCalendar
{
    public function __construct(
        protected ScheduleStatus $scheduleStatus
    ) {}

    /**
     * Bangun data grid kalender bulanan (pekan mulai Senin) beserta seluruh item agenda.
     *
     * @return array<string, mixed>
     */
    public function build(User $user, ?string $monthStr = null, bool $showCompleted = false): array
    {
        try {
            if (! $monthStr || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthStr)) {
                throw new \InvalidArgumentException('Format bulan tidak valid');
            }
            $firstDay = Carbon::createFromFormat('Y-m-d', "{$monthStr}-01")->startOfDay();
        } catch (\Throwable) {
            $monthStr = Carbon::today()->format('Y-m');
            $firstDay = Carbon::createFromFormat('Y-m-d', "{$monthStr}-01")->startOfDay();
        }

        $lastDay = $firstDay->copy()->endOfMonth()->startOfDay();

        // Senin sebagai hari pertama sepekan (ISO 8601: 1 = Senin, 7 = Minggu)
        $startOffset = $firstDay->dayOfWeekIso - 1;
        $gridStart = $firstDay->copy()->subDays($startOffset);

        $endOffset = 7 - $lastDay->dayOfWeekIso;
        $gridEnd = $lastDay->copy()->addDays($endOffset);

        $startDateStr = $gridStart->toDateString();
        $endDateStr = $gridEnd->toDateString();

        // 1. Ambil jadwal berbasis tanggal aktif
        /** @var Collection<int, MaintenanceSchedule> $schedules */
        $schedules = $user->schedules()
            ->with('subject')
            ->where('is_active', true)
            ->whereNotNull('next_due_date')
            ->whereBetween('next_due_date', [$startDateStr, $endDateStr])
            ->get();

        // 2. Ambil dokumen aktif yang jatuh tempo
        /** @var Collection<int, Document> $documents */
        $documents = $user->documents()
            ->with('subject')
            ->whereNull('archived_at')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$startDateStr, $endDateStr])
            ->get();

        // 3. Ambil catatan servis selesai jika toggle aktif
        /** @var Collection<int, MaintenanceRecord> $records */
        $records = collect();
        if ($showCompleted) {
            $records = $user->maintenanceRecords()
                ->with('subject')
                ->whereBetween('done_at', [$startDateStr, $endDateStr])
                ->get();
        }

        // Peta item per tanggal 'YYYY-MM-DD'
        /** @var array<string, Collection<int, Fluent>> $itemsByDate */
        $itemsByDate = [];

        foreach ($schedules as $sch) {
            $dateKey = Carbon::parse($sch->next_due_date)->toDateString();
            $status = $this->scheduleStatus->determine($sch);
            $chipClass = ScheduleStatus::chipClass($status);
            $statusLabel = ScheduleStatus::label($status);

            $isVehicle = $sch->subject instanceof Vehicle;
            $url = $isVehicle
                ? route('vehicles.index', ['v' => $sch->subject_id]).'#jadwal'
                : route('home-assets.show', $sch->subject_id).'#jadwal';

            $itemsByDate[$dateKey] = $itemsByDate[$dateKey] ?? collect();
            $itemsByDate[$dateKey]->push(new Fluent([
                'id' => "sch-{$sch->id}",
                'type' => 'jadwal',
                'type_label' => 'Jadwal Servis',
                'title' => $sch->title,
                'subject_name' => $sch->subject?->name ?? 'Aset/Kendaraan',
                'status' => $status,
                'status_label' => $statusLabel,
                'chip_class' => $chipClass,
                'priority' => $sch->priority,
                'url' => $url,
                'detail' => $sch->notes ?: $sch->trigger_label,
            ]));
        }

        foreach ($documents as $doc) {
            $dateKey = Carbon::parse($doc->expires_at)->toDateString();
            $status = $this->scheduleStatus->determineDocument($doc);
            $chipClass = $this->chipClassForStatus($status);
            $statusLabel = $this->labelForStatus($status);

            $isVehicle = $doc->subject instanceof Vehicle;
            $url = $isVehicle
                ? route('vehicles.index', ['v' => $doc->subject_id]).'#dokumen'
                : route('home-assets.show', $doc->subject_id).'#dokumen';

            $itemsByDate[$dateKey] = $itemsByDate[$dateKey] ?? collect();
            $itemsByDate[$dateKey]->push(new Fluent([
                'id' => "doc-{$doc->id}",
                'type' => 'dokumen',
                'type_label' => 'Pajak / Dokumen',
                'title' => $doc->title,
                'subject_name' => $doc->subject?->name ?? 'Aset/Kendaraan',
                'status' => $status,
                'status_label' => $statusLabel,
                'chip_class' => $chipClass,
                'priority' => 'normal',
                'url' => $url,
                'detail' => 'Jatuh tempo dokumen/pajak',
            ]));
        }

        foreach ($records as $rec) {
            $dateKey = Carbon::parse($rec->done_at)->toDateString();
            $isVehicle = $rec->subject instanceof Vehicle;
            $url = $isVehicle
                ? route('vehicles.index', ['v' => $rec->subject_id]).'#ringkasan'
                : route('home-assets.show', $rec->subject_id).'#ringkasan';

            $itemsByDate[$dateKey] = $itemsByDate[$dateKey] ?? collect();
            $itemsByDate[$dateKey]->push(new Fluent([
                'id' => "rec-{$rec->id}",
                'type' => 'selesai',
                'type_label' => 'Selesai',
                'title' => $rec->service_type ?: 'Servis Selesai',
                'subject_name' => $rec->subject?->name ?? 'Aset/Kendaraan',
                'status' => 'normal',
                'status_label' => 'Selesai',
                'chip_class' => 'chip-neutral',
                'priority' => 'normal',
                'url' => $url,
                'detail' => 'Riwayat perawatan selesai',
            ]));
        }

        // Susun grid hari kalender (pekan demi pekan)
        $weeks = [];
        $currentDate = $gridStart->copy();
        $agendaList = []; // Daftar agenda terurut per tanggal untuk tampilan mobile

        while ($currentDate->lte($gridEnd)) {
            $weekDays = [];
            for ($d = 0; $d < 7; $d++) {
                $dateStr = $currentDate->toDateString();
                $dayItems = $itemsByDate[$dateStr] ?? collect();

                // Urutkan item pada hari itu: terlambat > mendesak > perhatian > normal
                $priorityRank = ['terlambat' => 0, 'mendesak' => 1, 'perhatian' => 2, 'normal' => 3];
                $sortedDayItems = $dayItems->sortBy(fn (Fluent $i) => $priorityRank[$i->status] ?? 99)->values();

                $dayData = [
                    'date' => $currentDate->copy(),
                    'date_string' => $dateStr,
                    'day_number' => $currentDate->day,
                    'is_current_month' => $currentDate->month === $firstDay->month,
                    'is_today' => $currentDate->isToday(),
                    'items' => $sortedDayItems,
                ];

                $weekDays[] = $dayData;

                if ($dayData['is_current_month'] && $sortedDayItems->isNotEmpty()) {
                    $agendaList[$dateStr] = [
                        'date' => $currentDate->copy(),
                        'items' => $sortedDayItems,
                    ];
                }

                $currentDate->addDay();
            }
            $weeks[] = $weekDays;
        }

        // Hitung jumlah jadwal aktif berbasis KM yang tidak masuk kalender
        $kmSchedulesCount = $user->schedules()
            ->where('is_active', true)
            ->where('trigger_type', 'kilometer')
            ->count();

        return [
            'month' => $monthStr,
            'month_label' => $firstDay->translatedFormat('F Y'),
            'prev_month' => $firstDay->copy()->subMonth()->format('Y-m'),
            'next_month' => $firstDay->copy()->addMonth()->format('Y-m'),
            'weeks' => $weeks,
            'agenda_list' => $agendaList,
            'show_completed' => $showCompleted,
            'km_schedules_count' => $kmSchedulesCount,
        ];
    }

    protected function chipClassForStatus(string $status): string
    {
        return match ($status) {
            'terlambat' => 'chip-urgent',
            'mendesak' => 'chip-warning',
            'perhatian' => 'chip-warning',
            default => 'chip-success',
        };
    }

    protected function labelForStatus(string $status): string
    {
        return match ($status) {
            'terlambat' => 'Terlambat',
            'mendesak' => 'Mendesak',
            'perhatian' => 'Perhatian',
            default => 'Normal',
        };
    }
}
