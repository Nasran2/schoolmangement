<?php

namespace App\Http\Controllers;

use App\Services\ReportOverview;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ReportOverviewController extends Controller
{
    public function show(Request $request, string $metric, ReportOverview $overview): View
    {
        abort_unless(isset(ReportOverview::METRICS[$metric]), 404);
        abort_unless($overview->allowed($request->user(), $metric), 403);
        $filters = $overview->detailFilters($request, $metric);
        $rows = $overview->rows($metric, $filters);
        $total = round($rows->sum('amount'), 2);
        $totalCount = $rows->count();
        $filterOptions = $overview->detailOptions($metric, $rows, $filters);
        $rows = $overview->filterRows($rows, $filters);
        $filteredTotal = round($rows->sum('amount'), 2);
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $items = new LengthAwarePaginator($rows->forPage($page, 30)->values(), $rows->count(), 30, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('reports.overview-details', ['metric' => $metric, 'settings' => ReportOverview::METRICS[$metric], 'filters' => $filters,
            'items' => $items, 'total' => $total, 'totalCount' => $totalCount, 'filteredTotal' => $filteredTotal, 'filterOptions' => $filterOptions]);
    }
}
