<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pos\ReportRequest;
use App\Services\SalesReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(ReportRequest $request, SalesReportService $service): View
    {
        $filters = $request->validated();
        $report = $service->summary($filters['from'], $filters['to']);

        return view('reports.index', compact('report', 'filters'));
    }
}
