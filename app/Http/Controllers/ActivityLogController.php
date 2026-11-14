<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HomeAsset;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // ── Filters ──────────────────────────────────────────────────────────
        $filters = [
            'event' => $request->query('event', 'all'),
            'subject' => $request->query('subject', 'all'),
            'from' => $request->query('from', ''),
            'to' => $request->query('to', ''),
        ];

        // ── Base query ───────────────────────────────────────────────────────
        $query = ActivityLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        // Filter: kategori event
        if ($filters['event'] !== 'all') {
            $query->where('event', 'like', $filters['event'].'.%');
        }

        // Filter: subjek spesifik (format "vehicle:5" atau "home_asset:3")
        if ($filters['subject'] !== 'all') {
            [$type, $id] = explode(':', $filters['subject'].':');
            $modelClass = match ($type) {
                'vehicle' => Vehicle::class,
                'home_asset' => HomeAsset::class,
                default => null,
            };
            if ($modelClass && $id) {
                $query->where('subject_type', $modelClass)->where('subject_id', (int) $id);
            }
        }

        // Filter: rentang tanggal
        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        // ── Paginate ─────────────────────────────────────────────────────────
        $paginator = $query->with('subject')->paginate(30)->withQueryString();

        $total = $paginator->total();

        // ── Group by date ────────────────────────────────────────────────────
        $grouped = $paginator->getCollection()->groupBy(function (ActivityLog $log) {
            return $log->created_at->locale('id')->isoFormat('dddd, D MMMM YYYY');
        });

        // ── Sidebar options ──────────────────────────────────────────────────
        $vehicles = $user->vehicles()->orderBy('id')->get();
        $homeAssets = $user->homeAssets()->orderBy('name')->get();

        return view('activity-log.index', compact(
            'paginator',
            'grouped',
            'total',
            'filters',
            'vehicles',
            'homeAssets',
        ));
    }
}
