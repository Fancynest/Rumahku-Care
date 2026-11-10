<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\ExpenseReport;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function index(Request $request, ExpenseReport $report): View
    {
        $month = $request->query('month', Carbon::today()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = Carbon::today()->format('Y-m');
        }

        $filters = [
            'month' => $month,
            'category' => $request->query('category', 'all'),
            'subject' => $request->query('subject', 'all'),
        ];

        $page = (int) $request->query('page', 1);
        $data = $report->getMonthlyReport($request->user(), $filters, 20, $page);

        $vehicles = $request->user()->vehicles()->get();
        $homeAssets = $request->user()->homeAssets()->get();

        // Anggaran (Budgets)
        $budgets = $request->user()->budgets()->get();
        $totalBudget = $budgets->firstWhere('category', null);

        $budgetSummary = [
            'total' => $totalBudget ? $totalBudget->calculateProgress($data['total_this_month']) : null,
            'categories' => [],
            'has_any_budget' => $budgets->isNotEmpty(),
            'raw_budgets' => $budgets,
        ];

        foreach (['servis', 'bbm', 'perbaikan', 'dokumen'] as $catKey) {
            $catBudget = $budgets->firstWhere('category', $catKey);
            $catSpent = $data['breakdown'][$catKey]['amount'] ?? 0;
            $budgetSummary['categories'][$catKey] = $catBudget ? $catBudget->calculateProgress($catSpent) : null;
        }

        return view('finance.index', array_merge($data, [
            'filters' => $filters,
            'vehicles' => $vehicles,
            'homeAssets' => $homeAssets,
            'budgetSummary' => $budgetSummary,
        ]));
    }

    public function updateBudgets(Request $request): RedirectResponse
    {
        $rawBudgets = $request->input('budgets', []);

        $categories = [
            'total' => null,
            'servis' => 'servis',
            'bbm' => 'bbm',
            'perbaikan' => 'perbaikan',
            'dokumen' => 'dokumen',
        ];

        foreach ($categories as $inputKey => $categoryValue) {
            $val = $rawBudgets[$inputKey] ?? null;
            if (is_string($val)) {
                $val = preg_replace('/,.*$/', '', trim($val));
                $val = preg_replace('/[^0-9]/', '', $val);
            }
            $amount = (int) $val;

            if ($amount > 0) {
                Budget::updateOrCreate(
                    [
                        'user_id' => $request->user()->id,
                        'category' => $categoryValue,
                    ],
                    [
                        'monthly_amount' => $amount,
                    ]
                );
            } else {
                Budget::where('user_id', $request->user()->id)
                    ->where('category', $categoryValue)
                    ->delete();
            }
        }

        return redirect()->route('finance.index', ['month' => $request->query('month', date('Y-m'))])
            ->with('success', 'Pengaturan anggaran bulanan berhasil disimpan.');
    }

    public function export(Request $request, ExpenseReport $report): StreamedResponse
    {
        $month = $request->query('month', Carbon::today()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = Carbon::today()->format('Y-m');
        }

        $filters = [
            'month' => $month,
            'category' => $request->query('category', 'all'),
            'subject' => $request->query('subject', 'all'),
        ];

        $items = $report->getFilteredItems($request->user(), $filters);
        $filename = "laporan-pengeluaran-{$month}.csv";

        return response()->streamDownload(function () use ($items): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM untuk Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Header CSV
            fputcsv($handle, [
                'Tanggal',
                'Kategori',
                'Keterangan',
                'Subjek',
                'Jumlah',
            ]);

            // Helper untuk mencegah CSV Injection
            $sanitize = function (mixed $value): string {
                $str = (string) $value;
                if ($str === '') {
                    return $str;
                }

                $dangerous = ['=', '+', '-', '@', "\t", "\r"];

                if (in_array($str[0], $dangerous, true) || in_array(ltrim($str)[0] ?? '', $dangerous, true)) {
                    return "'".$str;
                }

                return $str;
            };

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->date->format('d/m/Y'),
                    $sanitize($item->category_label),
                    $sanitize($item->title),
                    $sanitize($item->subject_label),
                    $item->amount,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
