<?php

namespace App\Http\Controllers\Api\Private\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    protected $reportService;
    public function  __construct(ReportService $reportService)
    {
        $this->middleware('auth:api');
        $this->middleware('permission:all_reports', ['only' => ['__invoke']]);
        $this->reportService =$reportService;
    }
    public function __invoke()
    {
      return $this->reportService->reports();
    }
}
