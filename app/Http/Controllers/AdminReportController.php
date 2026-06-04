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
        $dailyTripRanges = $this->reportService->dailyTripRanges();
        $topRoutes = $this->reportService->topRoutes();
        $requestSummary = $this->reportService->requestDecisionSummary();
        $customRouteSummary = $this->reportService->customRouteSummary();
        $aiSupportSummary = $this->reportService->aiSupportSummary();
        $reliabilitySummary = $this->reportService->passengerReliabilitySummary();
        $thesisAlignment = $this->reportService->thesisAlignmentSummary();

        return view('admin.reports.index', compact(
            'overview',
            'paymentBreakdown',
            'monthlyReports',
            'dailyTripRanges',
            'topRoutes',
            'requestSummary',
            'customRouteSummary',
            'aiSupportSummary',
            'reliabilitySummary',
            'thesisAlignment'
        ));
    }

    public function exportCsv(): Response
    {
        $overview = $this->reportService->overview();
        $paymentBreakdown = $this->reportService->paymentStatusBreakdown();
        $monthlyReports = $this->reportService->monthlyTripSummaryForExport();
        $topRoutes = $this->reportService->topRoutes();
        $requestSummary = $this->reportService->requestDecisionSummary();
        $customRouteSummary = $this->reportService->customRouteSummary();
        $aiSupportSummary = $this->reportService->aiSupportSummary();
        $reliabilitySummary = $this->reportService->passengerReliabilitySummary();
        $thesisAlignment = $this->reportService->thesisAlignmentSummary();
        $filename = 'carpoolhub-admin-report-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($overview, $paymentBreakdown, $monthlyReports, $topRoutes, $requestSummary, $customRouteSummary, $aiSupportSummary, $reliabilitySummary, $thesisAlignment): void {
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

            fputcsv($handle, ['Thesis Module Evidence']);
            fputcsv($handle, ['Module', 'Evidence', 'Unit']);
            foreach ($thesisAlignment as $row) {
                fputcsv($handle, [$row['objective'], (string) $row['evidence'], $row['unit']]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Custom Route Preference']);
            foreach ($customRouteSummary as $key => $value) {
                fputcsv($handle, [$key, (string) $value]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Passenger Requests']);
            foreach ($requestSummary as $key => $value) {
                fputcsv($handle, [$key, (string) $value]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['AI Decision Support']);
            foreach ($aiSupportSummary as $key => $value) {
                fputcsv($handle, [$key, (string) $value]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Passenger Reliability']);
            foreach ($reliabilitySummary as $key => $value) {
                if ($key === 'by_level') {
                    continue;
                }
                fputcsv($handle, [$key, (string) $value]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Top Routes']);
            fputcsv($handle, ['Route', 'Trips', 'Average Fare', 'Drivers', 'Fare Total']);
            foreach ($topRoutes as $row) {
                fputcsv($handle, [
                    $row['route_name'],
                    (string) $row['trip_count'],
                    number_format((float) $row['avg_fare'], 2, '.', ''),
                    (string) $row['driver_count'],
                    number_format((float) $row['fare_total'], 2, '.', ''),
                ]);
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
        $topRoutes = $this->reportService->topRoutes();
        $requestSummary = $this->reportService->requestDecisionSummary();
        $customRouteSummary = $this->reportService->customRouteSummary();
        $aiSupportSummary = $this->reportService->aiSupportSummary();
        $reliabilitySummary = $this->reportService->passengerReliabilitySummary();
        $thesisAlignment = $this->reportService->thesisAlignmentSummary();

        return view('admin.reports.pdf', compact(
            'overview',
            'paymentBreakdown',
            'monthlyReports',
            'topRoutes',
            'requestSummary',
            'customRouteSummary',
            'aiSupportSummary',
            'reliabilitySummary',
            'thesisAlignment'
        ));
    }
}
