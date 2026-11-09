<?php

namespace App\Http\Controllers;

use App\Services\ExpenseReport;
use Carbon\Carbon;
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

        return view('finance.index', array_merge($data, [
            'filters' => $filters,
            'vehicles' => $vehicles,
            'homeAssets' => $homeAssets,
        ]));
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
                if ($str !== '' && in_array($str[0], ['=', '+', '-', '@'], true)) {
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
