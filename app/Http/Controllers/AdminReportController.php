<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;

class AdminReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function index(): View
    {
        $overview = $this->reportService->overview();
        $paymentBreakdown = $this->reportService->paymentStatusBreakdown();
        $cycleReports = $this->reportService->cycleReports();

        return view('admin.reports.index', compact('overview', 'paymentBreakdown', 'cycleReports'));
    }

    public function exportCsv(): Response
    {
        $overview = $this->reportService->overview();
        $paymentBreakdown = $this->reportService->paymentStatusBreakdown();
        $cycleReports = $this->reportService->cycleReportsForExport();
        $filename = 'carpoolhub-admin-report-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($overview, $paymentBreakdown, $cycleReports): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['CarpoolHub Admin Report']);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, []);

            fputcsv($handle, ['Overview']);
            foreach ($overview as $key => $value) {
                fputcsv($handle, [$key, is_numeric($value) ? (string) $value : $value]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Payment Breakdown']);
            fputcsv($handle, ['Status', 'Count', 'Amount']);
            foreach ($paymentBreakdown as $status => $row) {
                fputcsv($handle, [$status, (string) $row['count'], number_format((float) $row['amount'], 2, '.', '')]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Billing Cycle Financial Summary']);
            fputcsv($handle, ['Month', 'Status', 'Trips', 'Fare Total', 'Paid Total', 'Pending/Unpaid Total']);
            foreach ($cycleReports as $cycle) {
                fputcsv($handle, [
                    $cycle->month_key,
                    $cycle->status,
                    (string) $cycle->report_trip_count,
                    number_format((float) $cycle->report_fare_total, 2, '.', ''),
                    number_format((float) $cycle->report_paid_total, 2, '.', ''),
                    number_format((float) $cycle->report_pending_unpaid_total, 2, '.', ''),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdfView(): View|Factory|Application
    {
        $overview = $this->reportService->overview();
        $paymentBreakdown = $this->reportService->paymentStatusBreakdown();
        $cycleReports = $this->reportService->cycleReportsForExport();

        return view('admin.reports.pdf', compact('overview', 'paymentBreakdown', 'cycleReports'));
    }
}
