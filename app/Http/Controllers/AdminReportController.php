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
        $monthlyReports = $this->reportService->monthlyTripSummary();

        return view('admin.reports.index', compact('overview', 'paymentBreakdown', 'monthlyReports'));
    }

    public function exportCsv(): Response
    {
        $overview = $this->reportService->overview();
        $paymentBreakdown = $this->reportService->paymentStatusBreakdown();
        $monthlyReports = $this->reportService->monthlyTripSummaryForExport();
        $filename = 'carpoolhub-admin-report-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($overview, $paymentBreakdown, $monthlyReports): void {
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

            fputcsv($handle, ['Monthly Trip Summary']);
            fputcsv($handle, ['Month', 'Trips', 'Fare Total', 'Paid Total', 'Pending/Unpaid Total']);
            foreach ($monthlyReports as $row) {
                fputcsv($handle, [
                    $row['month_key'],
                    (string) $row['trip_count'],
                    number_format((float) $row['fare_total'], 2, '.', ''),
                    number_format((float) $row['paid_total'], 2, '.', ''),
                    number_format((float) $row['pending_unpaid_total'], 2, '.', ''),
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
        $monthlyReports = $this->reportService->monthlyTripSummaryForExport();

        return view('admin.reports.pdf', compact('overview', 'paymentBreakdown', 'monthlyReports'));
    }
}
