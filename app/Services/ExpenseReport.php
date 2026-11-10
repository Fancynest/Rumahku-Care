<?php

namespace App\Services;

use App\Models\DamageReport;
use App\Models\Document;
use App\Models\FuelLog;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

class ExpenseReport
{
    /**
     * Ambil seluruh pengeluaran ternormalisasi untuk pengguna, opsional dibatasi rentang tanggal.
     *
     * @return Collection<int, Fluent>
     */
    public function getAllExpenses(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $items = collect();

        // 1. Servis & Perawatan (maintenance_records)
        $recordsQuery = $user->maintenanceRecords()
            ->with('subject')
            ->where(function ($q) {
                $q->where('labor_cost', '>', 0)
                    ->orWhere('parts_cost', '>', 0);
            });
        $this->applyDateRange($recordsQuery, 'done_at', $startDate, $endDate);
        $records = $recordsQuery->get();

        foreach ($records as $r) {
            $total = (int) $r->labor_cost + (int) $r->parts_cost;
            if ($total <= 0 || ! $r->done_at) {
                continue;
            }

            $date = Carbon::parse($r->done_at)->startOfDay();
            $isVehicle = $r->subject instanceof Vehicle;
            $subjectLabel = $r->subject?->name ?? 'Aset/Kendaraan';
            $url = $isVehicle
                ? route('vehicles.index', ['v' => $r->subject_id]).'#ringkasan'
                : route('home-assets.show', $r->subject_id).'#ringkasan';

            $items->push(new Fluent([
                'id' => "servis-{$r->id}",
                'origin_id' => $r->id,
                'source_type' => MaintenanceRecord::class,
                'date' => $date,
                'category' => 'servis',
                'category_label' => 'Servis & Perawatan',
                'category_badge' => 'bg-primary-subtle text-primary border border-primary',
                'title' => $r->service_type ?: 'Servis Berkala',
                'subject_label' => $subjectLabel,
                'subject_type' => $isVehicle ? 'vehicle' : 'home_asset',
                'subject_id' => $r->subject_id,
                'amount' => $total,
                'formatted_amount' => 'Rp '.number_format($total, 0, ',', '.'),
                'url' => $url,
            ]));
        }

        // 2. BBM (fuel_logs)
        $fuelLogsQuery = $user->fuelLogs()
            ->with('vehicle')
            ->where('total_cost', '>', 0);
        $this->applyDateRange($fuelLogsQuery, 'filled_at', $startDate, $endDate);
        $fuelLogs = $fuelLogsQuery->get();

        foreach ($fuelLogs as $f) {
            if (! $f->filled_at || (int) $f->total_cost <= 0) {
                continue;
            }

            $date = Carbon::parse($f->filled_at)->startOfDay();
            $subjectLabel = $f->vehicle?->name ?? 'Kendaraan';
            $fuelGrade = $f->fuel_grade ? " ({$f->fuel_grade})" : '';
            $liters = number_format($f->liters, 1, ',', '.');
            $title = "BBM{$fuelGrade} • {$liters} L";
            $url = route('vehicles.index', ['v' => $f->vehicle_id]).'#bbm';

            $items->push(new Fluent([
                'id' => "bbm-{$f->id}",
                'origin_id' => $f->id,
                'source_type' => FuelLog::class,
                'date' => $date,
                'category' => 'bbm',
                'category_label' => 'BBM',
                'category_badge' => 'bg-success-subtle text-success border border-success',
                'title' => $title,
                'subject_label' => $subjectLabel,
                'subject_type' => 'vehicle',
                'subject_id' => $f->vehicle_id,
                'amount' => (int) $f->total_cost,
                'formatted_amount' => 'Rp '.number_format($f->total_cost, 0, ',', '.'),
                'url' => $url,
            ]));
        }

        // 3. Perbaikan Kerusakan (damage_reports) - HANYA selesai dan biaya > 0 (resolved_at)
        $damagesQuery = $user->damageReports()
            ->with('reportable')
            ->where('status', 'selesai')
            ->where('repair_cost', '>', 0)
            ->whereNotNull('resolved_at');
        $this->applyDateRange($damagesQuery, 'resolved_at', $startDate, $endDate);
        $damages = $damagesQuery->get();

        foreach ($damages as $d) {
            $date = Carbon::parse($d->resolved_at)->startOfDay();
            $isVehicle = $d->reportable instanceof Vehicle;
            $subjectLabel = $d->reportable?->name ?? 'Aset/Kendaraan';
            $url = $isVehicle
                ? route('vehicles.index', ['v' => $d->reportable_id]).'#kerusakan'
                : route('home-assets.show', $d->reportable_id).'#kerusakan';

            $items->push(new Fluent([
                'id' => "perbaikan-{$d->id}",
                'origin_id' => $d->id,
                'source_type' => DamageReport::class,
                'date' => $date,
                'category' => 'perbaikan',
                'category_label' => 'Perbaikan',
                'category_badge' => 'bg-danger-subtle text-danger border border-danger',
                'title' => $d->title,
                'subject_label' => $subjectLabel,
                'subject_type' => $isVehicle ? 'vehicle' : 'home_asset',
                'subject_id' => $d->reportable_id,
                'amount' => (int) $d->repair_cost,
                'formatted_amount' => 'Rp '.number_format($d->repair_cost, 0, ',', '.'),
                'url' => $url,
            ]));
        }

        // 4. Dokumen & Pajak (documents) - biaya > 0, termasuk yang diarsipkan
        $documentsQuery = $user->documents()
            ->with('subject')
            ->where('cost', '>', 0);

        if ($startDate || $endDate) {
            $documentsQuery->where(function ($q) use ($startDate, $endDate): void {
                $q->where(function ($sub) use ($startDate, $endDate): void {
                    $sub->whereNotNull('issued_at');
                    $this->applyDateRange($sub, 'issued_at', $startDate, $endDate);
                })->orWhere(function ($sub) use ($startDate, $endDate): void {
                    $sub->whereNull('issued_at');
                    if ($startDate && $endDate) {
                        $sub->whereBetween('created_at', [
                            $startDate->copy()->startOfDay()->toDateTimeString(),
                            $endDate->copy()->endOfDay()->toDateTimeString(),
                        ]);
                    } elseif ($startDate) {
                        $sub->where('created_at', '>=', $startDate->copy()->startOfDay()->toDateTimeString());
                    } elseif ($endDate) {
                        $sub->where('created_at', '<=', $endDate->copy()->endOfDay()->toDateTimeString());
                    }
                });
            });
        }

        $documents = $documentsQuery->get();

        foreach ($documents as $doc) {
            $rawDate = $doc->issued_at ?? $doc->created_at;
            if (! $rawDate) {
                continue;
            }

            $date = Carbon::parse($rawDate)->startOfDay();
            $isVehicle = $doc->subject instanceof Vehicle;
            $subjectLabel = $doc->subject?->name ?? 'Aset/Kendaraan';
            $url = $isVehicle
                ? route('vehicles.index', ['v' => $doc->subject_id]).'#dokumen'
                : route('home-assets.show', $doc->subject_id).'#dokumen';

            $items->push(new Fluent([
                'id' => "dokumen-{$doc->id}",
                'origin_id' => $doc->id,
                'source_type' => Document::class,
                'date' => $date,
                'category' => 'dokumen',
                'category_label' => 'Dokumen & Pajak',
                'category_badge' => 'bg-warning-subtle text-warning-emphasis border border-warning',
                'title' => $doc->title,
                'subject_label' => $subjectLabel,
                'subject_type' => $isVehicle ? 'vehicle' : 'home_asset',
                'subject_id' => $doc->subject_id,
                'amount' => (int) $doc->cost,
                'formatted_amount' => 'Rp '.number_format($doc->cost, 0, ',', '.'),
                'url' => $url,
            ]));
        }

        return $items->sortByDesc(fn (Fluent $i) => $i->date->timestamp.'-'.$i->id)->values();
    }

    /**
     * Terapkan rentang tanggal pada query Eloquent.
     *
     * @param  Builder|Relation  $query
     */
    private function applyDateRange(mixed $query, string $column, ?Carbon $startDate, ?Carbon $endDate): void
    {
        if ($startDate && $endDate) {
            $query->whereBetween($column, [
                $startDate->toDateString().' 00:00:00',
                $endDate->toDateString().' 23:59:59',
            ]);
        } elseif ($startDate) {
            $query->where($column, '>=', $startDate->toDateString().' 00:00:00');
        } elseif ($endDate) {
            $query->where($column, '<=', $endDate->toDateString().' 23:59:59');
        }
    }

    /**
     * Filter daftar pengeluaran berdasarkan bulan, kategori, dan subjek.
     *
     * @param  array{month?: string|null, category?: string|null, subject?: string|null}  $filters
     * @return Collection<int, Fluent>
     */
    public function getFilteredItems(User $user, array $filters = [], ?Collection $sourceExpenses = null): Collection
    {
        if ($sourceExpenses !== null) {
            $all = $sourceExpenses;
        } else {
            $startDate = null;
            $endDate = null;
            if (! empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', $filters['month'])) {
                $startDate = Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
            }
            $all = $this->getAllExpenses($user, $startDate, $endDate);
        }

        // Filter Bulan (YYYY-MM)
        if (! empty($filters['month'])) {
            $month = $filters['month'];
            $all = $all->filter(fn (Fluent $i) => $i->date->format('Y-m') === $month);
        }

        // Filter Kategori
        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $all = $all->filter(fn (Fluent $i) => $i->category === $filters['category']);
        }

        // Filter Subjek (misal: "vehicle:1" atau "home_asset:2")
        if (! empty($filters['subject']) && $filters['subject'] !== 'all') {
            [$type, $id] = explode(':', $filters['subject']);
            $all = $all->filter(fn (Fluent $i) => $i->subject_type === $type && (int) $i->subject_id === (int) $id);
        }

        return $all->values();
    }

    /**
     * Dapatkan ringkasan laporan keuangan bulanan lengkap (statistik, breakdown, tren 6 bulan).
     *
     * @param  array{month?: string|null, category?: string|null, subject?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function getMonthlyReport(User $user, array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $monthStr = $filters['month'] ?? Carbon::today()->format('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $monthStr)) {
            $monthStr = Carbon::today()->format('Y-m');
        }

        $selectedMonth = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
        $prevMonth = $selectedMonth->copy()->subMonth();

        // Rentang 6 bulan ke belakang sampai akhir bulan terpilih (5 bulan lalu + bulan terpilih)
        $trendStartDate = $selectedMonth->copy()->subMonths(5)->startOfMonth();
        $trendEndDate = $selectedMonth->copy()->endOfMonth();

        $allExpenses = $this->getAllExpenses($user, $trendStartDate, $trendEndDate);

        // Subject filter for broader trend
        $subjectFiltered = $allExpenses;
        if (! empty($filters['subject']) && $filters['subject'] !== 'all') {
            [$type, $id] = explode(':', $filters['subject']);
            $subjectFiltered = $subjectFiltered->filter(fn (Fluent $i) => $i->subject_type === $type && (int) $i->subject_id === (int) $id);
        }

        // Total bulan ini (sebelum filter kategori agar perbandingan kategori utuh)
        $thisMonthAll = $subjectFiltered->filter(fn (Fluent $i) => $i->date->format('Y-m') === $monthStr);
        $totalThisMonth = (int) $thisMonthAll->sum('amount');

        // Total bulan lalu
        $prevMonthStr = $prevMonth->format('Y-m');
        $prevMonthAll = $subjectFiltered->filter(fn (Fluent $i) => $i->date->format('Y-m') === $prevMonthStr);
        $totalPrevMonth = (int) $prevMonthAll->sum('amount');

        $diffAmount = $totalThisMonth - $totalPrevMonth;
        $diffPercentage = $totalPrevMonth > 0 ? round(($diffAmount / $totalPrevMonth) * 100, 1) : null;

        // Breakdown 4 kategori bulan ini
        $categoriesMeta = [
            'servis' => ['label' => 'Servis & Perawatan', 'color' => '#2563eb', 'badge' => 'bg-primary'],
            'bbm' => ['label' => 'Bahan Bakar (BBM)', 'color' => '#10b981', 'badge' => 'bg-success'],
            'perbaikan' => ['label' => 'Perbaikan Kerusakan', 'color' => '#ef4444', 'badge' => 'bg-danger'],
            'dokumen' => ['label' => 'Dokumen & Pajak', 'color' => '#f59e0b', 'badge' => 'bg-warning'],
        ];

        $breakdown = [];
        foreach ($categoriesMeta as $catKey => $meta) {
            $catTotal = (int) $thisMonthAll->where('category', $catKey)->sum('amount');
            $percentage = $totalThisMonth > 0 ? round(($catTotal / $totalThisMonth) * 100, 1) : 0;
            $breakdown[$catKey] = [
                'key' => $catKey,
                'label' => $meta['label'],
                'color' => $meta['color'],
                'amount' => $catTotal,
                'formatted_amount' => 'Rp '.number_format($catTotal, 0, ',', '.'),
                'percentage' => $percentage,
            ];
        }

        // Tren 6 bulan (DB-Agnostic, dihitung di PHP/Carbon)
        $sixMonthTrend = [];
        $maxMonthTotal = 0;

        for ($i = 5; $i >= 0; $i--) {
            $m = $selectedMonth->copy()->subMonths($i);
            $mKey = $m->format('Y-m');
            $mLabel = $m->translatedFormat('M Y');
            $mItems = $subjectFiltered->filter(fn (Fluent $item) => $item->date->format('Y-m') === $mKey);

            $mTotal = (int) $mItems->sum('amount');
            if ($mTotal > $maxMonthTotal) {
                $maxMonthTotal = $mTotal;
            }

            $sixMonthTrend[] = [
                'month' => $mKey,
                'label' => $mLabel,
                'short_label' => $m->translatedFormat('M'),
                'total' => $mTotal,
                'formatted_total' => 'Rp '.number_format($mTotal, 0, ',', '.'),
                'is_selected' => $mKey === $monthStr,
                'servis' => (int) $mItems->where('category', 'servis')->sum('amount'),
                'bbm' => (int) $mItems->where('category', 'bbm')->sum('amount'),
                'perbaikan' => (int) $mItems->where('category', 'perbaikan')->sum('amount'),
                'dokumen' => (int) $mItems->where('category', 'dokumen')->sum('amount'),
            ];
        }

        // Tambahkan rasio tinggi bar untuk CSS
        foreach ($sixMonthTrend as &$bar) {
            $bar['height_percent'] = $maxMonthTotal > 0
                ? max(6, (int) round(($bar['total'] / $maxMonthTotal) * 100))
                : 0;
        }
        unset($bar);

        // Items bulan ini dengan filter kategori & subjek aktif
        $filteredItems = $this->getFilteredItems($user, [
            'month' => $monthStr,
            'category' => $filters['category'] ?? null,
            'subject' => $filters['subject'] ?? null,
        ], $subjectFiltered);

        // Paginasi 20 item per halaman
        $totalItems = $filteredItems->count();
        $sliced = $filteredItems->slice(($page - 1) * $perPage, $perPage)->values();
        $paginated = new LengthAwarePaginator(
            $sliced,
            $totalItems,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return [
            'month' => $monthStr,
            'month_label' => $selectedMonth->translatedFormat('F Y'),
            'prev_month' => $prevMonth->format('Y-m'),
            'next_month' => $selectedMonth->copy()->addMonth()->format('Y-m'),
            'total_this_month' => $totalThisMonth,
            'formatted_total_this_month' => 'Rp '.number_format($totalThisMonth, 0, ',', '.'),
            'total_prev_month' => $totalPrevMonth,
            'diff_amount' => $diffAmount,
            'diff_percentage' => $diffPercentage,
            'breakdown' => $breakdown,
            'six_month_trend' => $sixMonthTrend,
            'items' => $paginated,
            'raw_filtered_count' => $totalItems,
            'categories' => $categoriesMeta,
        ];
    }
}
