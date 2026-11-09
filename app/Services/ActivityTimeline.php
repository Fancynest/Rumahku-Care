<?php

namespace App\Services;

use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

class ActivityTimeline
{
    /**
     * Bangun linimasa aktivitas terpadu lintas 5 sumber data.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters = []): array
    {
        $typeFilter = $filters['type'] ?? 'all';
        $subjectFilter = $filters['subject'] ?? 'all';
        $fromDate = ! empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $toDate = ! empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = 20;

        $targetSubjectType = null;
        $targetSubjectId = null;

        if ($subjectFilter !== 'all' && str_contains($subjectFilter, ':')) {
            [$prefix, $idStr] = explode(':', $subjectFilter, 2);
            $targetSubjectId = (int) $idStr;
            $targetSubjectType = $prefix === 'vehicle' ? Vehicle::class : HomeAsset::class;
        }

        /** @var Collection<int, Fluent> $events */
        $events = collect();

        // 1. Maintenance Records (Servis / Perawatan Selesai)
        if (in_array($typeFilter, ['all', 'maintenance'])) {
            $query = $user->maintenanceRecords()
                ->with('subject')
                ->latest('done_at')
                ->limit(200);

            if ($targetSubjectType) {
                $query->where('subject_type', $targetSubjectType)->where('subject_id', $targetSubjectId);
            }
            if ($fromDate) {
                $query->where('done_at', '>=', $fromDate->toDateString());
            }
            if ($toDate) {
                $query->where('done_at', '<=', $toDate->toDateString());
            }

            foreach ($query->get() as $rec) {
                $dt = Carbon::parse($rec->done_at)->startOfDay();
                $isVehicle = $rec->subject_type === Vehicle::class;
                $url = $isVehicle
                    ? route('vehicles.index', ['v' => $rec->subject_id]).'#ringkasan'
                    : route('home-assets.show', $rec->subject_id).'#ringkasan';

                $summaryParts = [];
                if ($rec->cost > 0) {
                    $summaryParts[] = 'Biaya: Rp '.number_format($rec->cost, 0, ',', '.');
                }
                if ($rec->odometer) {
                    $summaryParts[] = 'Odo: '.number_format($rec->odometer, 0, ',', '.').' km';
                }
                if ($rec->workshop) {
                    $summaryParts[] = $rec->workshop;
                }

                $events->push(new Fluent([
                    'id' => "maint-{$rec->id}",
                    'datetime' => $dt,
                    'type' => 'maintenance',
                    'type_label' => 'Perawatan',
                    'icon' => 'bi-wrench-adjustable',
                    'chip_class' => 'chip-success',
                    'title' => $rec->service_type ?: 'Servis Berkala Selesai',
                    'subject_name' => $rec->subject?->name ?? ($isVehicle ? 'Kendaraan' : 'Aset Rumah'),
                    'summary' => implode(' • ', $summaryParts) ?: 'Perawatan rutin selesai',
                    'url' => $url,
                ]));
            }
        }

        // 2. Fuel Logs (Pengisian BBM)
        if (in_array($typeFilter, ['all', 'fuel'])) {
            if ($targetSubjectType !== HomeAsset::class) {
                $query = $user->fuelLogs()
                    ->with('vehicle')
                    ->latest('filled_at')
                    ->limit(200);

                if ($targetSubjectType === Vehicle::class) {
                    $query->where('vehicle_id', $targetSubjectId);
                }
                if ($fromDate) {
                    $query->where('filled_at', '>=', $fromDate->toDateString());
                }
                if ($toDate) {
                    $query->where('filled_at', '<=', $toDate->toDateString());
                }

                foreach ($query->get() as $fuel) {
                    $dt = Carbon::parse($fuel->filled_at)->startOfDay();
                    $litersFormatted = rtrim(rtrim(number_format((float) $fuel->liters, 2, ',', '.'), '0'), ',');
                    $events->push(new Fluent([
                        'id' => "fuel-{$fuel->id}",
                        'datetime' => $dt,
                        'type' => 'fuel',
                        'type_label' => 'BBM',
                        'icon' => 'bi-fuel-pump',
                        'chip_class' => 'chip-neutral',
                        'title' => 'Isi BBM '.($fuel->fuel_grade ?: 'Bensin')." ({$litersFormatted} L)",
                        'subject_name' => $fuel->vehicle?->name ?? 'Kendaraan',
                        'summary' => 'Rp '.number_format($fuel->total_cost, 0, ',', '.').' • Odo: '.number_format($fuel->odometer, 0, ',', '.').' km',
                        'url' => route('vehicles.index', ['v' => $fuel->vehicle_id]).'#bbm',
                    ]));
                }
            }
        }

        // 3. Damage Reports (Laporan Kerusakan & Penyelesaian)
        if (in_array($typeFilter, ['all', 'damage'])) {
            $query = $user->damageReports()
                ->with('reportable')
                ->latest('reported_at')
                ->limit(200);

            if ($targetSubjectType) {
                $query->where('reportable_type', $targetSubjectType)->where('reportable_id', $targetSubjectId);
            }

            foreach ($query->get() as $damage) {
                $isVehicle = $damage->reportable_type === Vehicle::class;
                $url = $isVehicle
                    ? route('vehicles.index', ['v' => $damage->reportable_id]).'#kerusakan'
                    : route('home-assets.show', $damage->reportable_id).'#kerusakan';

                // Kejadian 1: Dilaporkan
                $repDt = Carbon::parse($damage->reported_at);
                $repDateStr = $repDt->toDateString();
                $inFrom = ! $fromDate || $repDateStr >= $fromDate->toDateString();
                $inTo = ! $toDate || $repDateStr <= $toDate->toDateString();

                if ($inFrom && $inTo) {
                    $severityLabel = match ($damage->severity) {
                        'berat' => 'Berat',
                        'sedang' => 'Sedang',
                        default => 'Ringan',
                    };
                    $events->push(new Fluent([
                        'id' => "dmg-rep-{$damage->id}",
                        'datetime' => $repDt,
                        'type' => 'damage',
                        'type_label' => 'Kerusakan Dilaporkan',
                        'icon' => 'bi-exclamation-triangle',
                        'chip_class' => $damage->severity === 'berat' ? 'chip-urgent' : 'chip-warning',
                        'title' => "Laporan Kerusakan: {$damage->title}",
                        'subject_name' => $damage->reportable?->name ?? 'Aset/Kendaraan',
                        'summary' => "Tingkat: {$severityLabel} • Status: ".ucfirst($damage->status),
                        'url' => $url,
                    ]));
                }

                // Kejadian 2: Selesai Diperbaiki (jika status selesai dan resolved_at terisi)
                if ($damage->status === 'selesai' && $damage->resolved_at) {
                    $resDt = Carbon::parse($damage->resolved_at);
                    $resDateStr = $resDt->toDateString();
                    $resInFrom = ! $fromDate || $resDateStr >= $fromDate->toDateString();
                    $resInTo = ! $toDate || $resDateStr <= $toDate->toDateString();

                    if ($resInFrom && $resInTo) {
                        $costStr = $damage->repair_cost > 0
                            ? 'Biaya: Rp '.number_format($damage->repair_cost, 0, ',', '.')
                            : 'Perbaikan rampung';

                        $events->push(new Fluent([
                            'id' => "dmg-res-{$damage->id}",
                            'datetime' => $resDt,
                            'type' => 'damage',
                            'type_label' => 'Kerusakan Selesai',
                            'icon' => 'bi-check2-circle',
                            'chip_class' => 'chip-success',
                            'title' => "Perbaikan Selesai: {$damage->title}",
                            'subject_name' => $damage->reportable?->name ?? 'Aset/Kendaraan',
                            'summary' => $costStr.($damage->repair_notes ? " • {$damage->repair_notes}" : ''),
                            'url' => $url,
                        ]));
                    }
                }
            }
        }

        // 4. Inspection Runs (Hasil Inspeksi Selesai)
        if (in_array($typeFilter, ['all', 'inspection'])) {
            $query = $user->inspectionRuns()
                ->with(['inspectable', 'items'])
                ->latest('inspected_at')
                ->limit(200);

            if ($targetSubjectType) {
                $query->where('inspectable_type', $targetSubjectType)->where('inspectable_id', $targetSubjectId);
            }
            if ($fromDate) {
                $query->where('inspected_at', '>=', $fromDate->toDateString());
            }
            if ($toDate) {
                $query->where('inspected_at', '<=', $toDate->toDateString());
            }

            foreach ($query->get() as $run) {
                $dt = Carbon::parse($run->inspected_at)->startOfDay();
                $isVehicle = $run->inspectable_type === Vehicle::class;
                $url = $isVehicle
                    ? route('vehicles.index', ['v' => $run->inspectable_id]).'#inspeksi'
                    : route('home-assets.show', $run->inspectable_id).'#inspeksi';

                $hasIssues = $run->has_issues;
                $events->push(new Fluent([
                    'id' => "insp-{$run->id}",
                    'datetime' => $dt,
                    'type' => 'inspection',
                    'type_label' => 'Inspeksi Selesai',
                    'icon' => 'bi-clipboard-check',
                    'chip_class' => $hasIssues ? 'chip-warning' : 'chip-success',
                    'title' => "Inspeksi: {$run->title}",
                    'subject_name' => $run->inspectable?->name ?? 'Aset/Kendaraan',
                    'summary' => $hasIssues ? 'Ditemukan butir yang perlu perhatian' : 'Semua butir inspeksi dalam kondisi baik',
                    'url' => $url,
                ]));
            }
        }

        // 5. Documents (Dokumen Dibuat / Diperpanjang)
        if (in_array($typeFilter, ['all', 'document'])) {
            $query = $user->documents()
                ->with('subject')
                ->latest('created_at')
                ->limit(200);

            if ($targetSubjectType) {
                $query->where('subject_type', $targetSubjectType)->where('subject_id', $targetSubjectId);
            }

            foreach ($query->get() as $doc) {
                $dt = Carbon::parse($doc->issued_at ?: $doc->created_at)->startOfDay();
                $dateStr = $dt->toDateString();
                $inFrom = ! $fromDate || $dateStr >= $fromDate->toDateString();
                $inTo = ! $toDate || $dateStr <= $toDate->toDateString();

                if ($inFrom && $inTo) {
                    $isVehicle = $doc->subject_type === Vehicle::class;
                    $url = $isVehicle
                        ? route('vehicles.index', ['v' => $doc->subject_id]).'#dokumen'
                        : route('home-assets.show', $doc->subject_id).'#dokumen';

                    $summaryParts = [];
                    if ($doc->cost > 0) {
                        $summaryParts[] = 'Biaya: Rp '.number_format($doc->cost, 0, ',', '.');
                    }
                    if ($doc->expires_at) {
                        $summaryParts[] = 'Jatuh tempo: '.Carbon::parse($doc->expires_at)->format('d M Y');
                    }

                    $events->push(new Fluent([
                        'id' => "doc-{$doc->id}",
                        'datetime' => $dt,
                        'type' => 'document',
                        'type_label' => 'Dokumen / Pajak',
                        'icon' => 'bi-file-earmark-text',
                        'chip_class' => 'chip-neutral',
                        'title' => "Dokumen: {$doc->title}",
                        'subject_name' => $doc->subject?->name ?? 'Aset/Kendaraan',
                        'summary' => implode(' • ', $summaryParts) ?: 'Dokumen tersimpan',
                        'url' => $url,
                    ]));
                }
            }
        }

        // Urutkan seluruh kejadian: terbaru lebih dulu (timestamp descending), lalu tie-breaker id
        $sortedEvents = $events->sort(function (Fluent $a, Fluent $b) {
            $cmp = $b->datetime->timestamp <=> $a->datetime->timestamp;
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) $b->id, (string) $a->id);
        })->values();

        // Paginasi 20 item per halaman
        $total = $sortedEvents->count();
        $sliced = $sortedEvents->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $sliced,
            $total,
            $perPage,
            $page,
            [
                'path' => route('schedules.history'),
                'query' => request()->query(),
            ]
        );

        // Kelompokkan 20 item halaman ini per tanggal
        /** @var Collection<string, Collection<int, Fluent>> $groupedByDate */
        $groupedByDate = $paginator->getCollection()->groupBy(function (Fluent $item) {
            return $item->datetime->translatedFormat('l, d F Y');
        });

        // Opsi subjek untuk dropdown filter
        $vehicles = $user->vehicles()->orderBy('plate')->get();
        $homeAssets = $user->homeAssets()->orderBy('name')->get();

        return [
            'paginator' => $paginator,
            'grouped_by_date' => $groupedByDate,
            'vehicles' => $vehicles,
            'home_assets' => $homeAssets,
            'filters' => [
                'type' => $typeFilter,
                'subject' => $subjectFilter,
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
            'total_events' => $total,
        ];
    }
}
