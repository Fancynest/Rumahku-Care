<?php

namespace App\Services;

use App\Models\DamageReport;
use App\Models\Document;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

class AttentionFeed
{
    /**
     * @var array<int, Collection<int, Fluent>>
     */
    private array $cachedFeed = [];

    public function __construct(
        protected ScheduleStatus $scheduleStatus
    ) {}

    /**
     * Ambil seluruh umpan perhatian (jadwal, dokumen, kerusakan) untuk pengguna.
     *
     * @return Collection<int, Fluent>
     */
    public function get(User $user): Collection
    {
        if (! isset($this->cachedFeed[$user->id])) {
            $this->cachedFeed[$user->id] = $this->build($user);
        }

        return $this->cachedFeed[$user->id];
    }

    /**
     * Ambil item yang perlu perhatian segera (terlambat, mendesak, perhatian).
     *
     * @return Collection<int, Fluent>
     */
    public function urgent(User $user): Collection
    {
        return $this->get($user)
            ->filter(fn (Fluent $item) => in_array($item->status, ['terlambat', 'mendesak', 'perhatian']))
            ->values();
    }

    /**
     * Hitung jumlah item kritis (terlambat atau mendesak).
     */
    public function urgentCount(User $user): int
    {
        return $this->get($user)
            ->filter(fn (Fluent $item) => in_array($item->status, ['terlambat', 'mendesak']))
            ->count();
    }

    /**
     * Bangun umpan ternormalisasi dari seluruh sumber data.
     *
     * @return Collection<int, Fluent>
     */
    protected function build(User $user): Collection
    {
        $items = collect();

        // 1. Dari Jadwal Perawatan (MaintenanceSchedule)
        $schedules = $this->scheduleStatus->forUser($user);
        foreach ($schedules as $sch) {
            $isVehicle = $sch->subject instanceof Vehicle;
            $subjectOdo = $isVehicle ? (int) $sch->subject->odometer : 0;

            $dueLabel = 'Jadwal Rutin';
            $dueDetail = '';

            if ($sch->trigger_type === 'kilometer') {
                $diff = (int) $sch->next_due_km - $subjectOdo;
                if ($diff <= 0) {
                    $dueLabel = 'Terlewat '.number_format(abs($diff)).' KM';
                } else {
                    $dueLabel = 'Sisa '.number_format($diff).' KM';
                }
                $dueDetail = 'Target: '.number_format($sch->next_due_km).' KM';
            } elseif ($sch->next_due_date) {
                $daysDiff = Carbon::today()->diffInDays(Carbon::parse($sch->next_due_date), false);
                if ($daysDiff < 0) {
                    $dueLabel = 'Terlambat '.abs($daysDiff).' hari';
                } else {
                    $dueLabel = 'Sisa '.$daysDiff.' hari lagi';
                }
                $dueDetail = 'Jatuh tempo: '.Carbon::parse($sch->next_due_date)->format('d M Y');
            }

            $url = $isVehicle
                ? route('vehicles.index', ['v' => $sch->subject_id]).'#jadwal'
                : route('home-assets.show', $sch->subject_id).'#jadwal';

            $items->push(new Fluent([
                'type' => 'jadwal',
                'type_badge' => 'Jadwal',
                'id' => $sch->id,
                'title' => $sch->title,
                'subject_name' => $sch->subject_name,
                'subject_type' => $sch->subject_type,
                'subject_id' => $sch->subject_id,
                'is_vehicle' => $isVehicle,
                'subject_odometer' => $subjectOdo,
                'status' => $sch->status,
                'status_label' => $sch->status_label,
                'status_chip_class' => $sch->status_chip_class,
                'priority' => $sch->priority,
                'due_label' => $dueLabel,
                'due_detail' => $dueDetail,
                'url' => $url,
                'trigger_label' => $sch->trigger_label,
                'source' => $sch,
            ]));
        }

        // 2. Dari Dokumen, Pajak, Garansi (Document)
        $documents = $user->documents()
            ->with('subject')
            ->active()
            ->whereNotNull('expires_at')
            ->get();

        foreach ($documents as $doc) {
            $isVehicle = $doc->subject instanceof Vehicle;
            $status = $doc->status;

            if (! $status) {
                continue;
            }

            $priority = match ($status) {
                'terlambat' => 'darurat',
                'mendesak' => 'mendesak',
                'perhatian' => 'perhatian',
                default => 'normal',
            };

            $url = $isVehicle
                ? route('vehicles.index', ['v' => $doc->subject_id]).'#dokumen'
                : route('home-assets.show', $doc->subject_id).'#dokumen';

            $items->push(new Fluent([
                'type' => 'dokumen',
                'type_badge' => $doc->type_label,
                'id' => $doc->id,
                'title' => $doc->title,
                'subject_name' => $doc->subject_name,
                'subject_type' => $doc->subject_type,
                'subject_id' => $doc->subject_id,
                'is_vehicle' => $isVehicle,
                'subject_odometer' => 0,
                'status' => $status,
                'status_label' => $doc->status_label,
                'status_chip_class' => $doc->status_chip_class,
                'priority' => $priority,
                'due_label' => $doc->due_label,
                'due_detail' => 'Jatuh tempo: '.$doc->expires_at?->format('d M Y'),
                'url' => $url,
                'trigger_label' => 'Kedaluwarsa',
                'source' => $doc,
            ]));
        }

        // 3. Dari Kerusakan (DamageReport) - jika tabel sudah ada di DB
        if (class_exists(DamageReport::class)) {
            $damageReports = $user->damageReports()
                ->with('reportable')
                ->whereIn('status', ['dilaporkan', 'dalam_perbaikan'])
                ->whereIn('severity', ['berat', 'sedang'])
                ->get();

            foreach ($damageReports as $dr) {
                $isVehicle = $dr->reportable instanceof Vehicle;
                $status = $dr->severity === 'berat' ? 'mendesak' : 'perhatian';
                $priority = $dr->severity === 'berat' ? 'darurat' : 'perhatian';

                $url = $isVehicle
                    ? route('vehicles.index', ['v' => $dr->reportable_id]).'#kerusakan'
                    : route('home-assets.show', $dr->reportable_id).'#kerusakan';

                $items->push(new Fluent([
                    'type' => 'kerusakan',
                    'type_badge' => 'Kerusakan ('.ucfirst($dr->severity).')',
                    'id' => $dr->id,
                    'title' => $dr->title,
                    'subject_name' => $dr->subject_name,
                    'subject_type' => $dr->reportable_type,
                    'subject_id' => $dr->reportable_id,
                    'is_vehicle' => $isVehicle,
                    'subject_odometer' => 0,
                    'status' => $status,
                    'status_label' => ucfirst($dr->status_label),
                    'status_chip_class' => $status === 'mendesak' ? 'chip-urgent' : 'chip-warning',
                    'priority' => $priority,
                    'due_label' => 'Perlu Perbaikan',
                    'due_detail' => 'Dilaporkan: '.$dr->reported_at?->format('d M Y'),
                    'url' => $url,
                    'trigger_label' => ucfirst($dr->severity),
                    'source' => $dr,
                ]));
            }
        }

        // Urutkan berdasarkan status (terlambat > mendesak > perhatian > normal) lalu tie-breaker prioritas
        $statusRank = [
            'terlambat' => 0,
            'mendesak' => 1,
            'perhatian' => 2,
            'normal' => 3,
        ];

        $priorityRank = [
            'darurat' => 0,
            'mendesak' => 1,
            'perhatian' => 2,
            'normal' => 3,
        ];

        return $items->sort(function (Fluent $a, Fluent $b) use ($statusRank, $priorityRank) {
            $statusCmp = ($statusRank[$a->status] ?? 99) <=> ($statusRank[$b->status] ?? 99);
            if ($statusCmp !== 0) {
                return $statusCmp;
            }

            return ($priorityRank[$a->priority] ?? 99) <=> ($priorityRank[$b->priority] ?? 99);
        })->values();
    }
}
