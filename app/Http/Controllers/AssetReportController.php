<?php

namespace App\Http\Controllers;

use App\Models\AssetBast;
use App\Models\AssetInspection;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AssetReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        $bastQuery = AssetBast::with(['asset', 'recipientUser'])
            ->whereBetween('bast_date', [$startDate->toDateString(), $endDate->toDateString()]);
        $inspectionQuery = AssetInspection::with(['asset', 'inspector'])
            ->whereBetween('inspection_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $basts = (clone $bastQuery)->latest('bast_date')->limit(12)->get();
        $inspections = (clone $inspectionQuery)->latest('inspection_date')->limit(12)->get();

        $stats = [
            'bast_total' => (clone $bastQuery)->count(),
            'bast_signed' => (clone $bastQuery)->where('status', AssetBast::STATUS_SIGNED)->count(),
            'inspection_total' => (clone $inspectionQuery)->count(),
            'inspection_issue' => (clone $inspectionQuery)
                ->whereIn('result', [AssetInspection::RESULT_NEEDS_REPAIR, AssetInspection::RESULT_REPLACE, AssetInspection::RESULT_RETIRE])
                ->count(),
        ];

        $bastByType = (clone $bastQuery)
            ->selectRaw('bast_type, count(*) as total')
            ->groupBy('bast_type')
            ->pluck('total', 'bast_type');
        $inspectionByResult = (clone $inspectionQuery)
            ->selectRaw('result, count(*) as total')
            ->groupBy('result')
            ->pluck('total', 'result');

        return view('assets.reports.index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'stats' => $stats,
            'basts' => $basts,
            'inspections' => $inspections,
            'bastByType' => $bastByType,
            'inspectionByResult' => $inspectionByResult,
        ]);
    }
}
