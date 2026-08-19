<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    /**
     * The nine ReportService calls every admin report view needs. Was copied
     * into index(), exportCsv() and exportPdfView() separately — a metric
     * added to the report page had to be remembered in three places to also
     * reach the exports. $forExport switches monthlyReports to the export's
     * longer 24-month window; everything else is identical either way.
     */
    private function sharedReportData(bool $forExport = false): array
    {
        return [
            'overview' => $this->reportService->overview(),
            'paymentBreakdown' => $this->reportService->paymentStatusBreakdown(),
            'monthlyReports' => $forExport
                ? $this->reportService->monthlyTripSummaryForExport()
                : $this->reportService->monthlyTripSummary(),
            'topRoutes' => $this->reportService->topRoutes(),
            'requestSummary' => $this->reportService->requestDecisionSummary(),
            'customRouteSummary' => $this->reportService->customRouteSummary(),
            'aiSupportSummary' => $this->reportService->aiSupportSummary(),
            'reliabilitySummary' => $this->reportService->passengerReliabilitySummary(),
            'thesisAlignment' => $this->reportService->thesisAlignmentSummary(),
        ];
    }

    public function index(): View
    {
        $data = $this->sharedReportData();
        $data['dailyTripRanges'] = $this->reportService->dailyTripRanges();

        return view('admin.reports.index', $data);
    }

    public function exportCsv(): StreamedResponse
    {
        [
            'overview' => $overview,
            'paymentBreakdown' => $paymentBreakdown,
            'monthlyReports' => $monthlyReports,
            'topRoutes' => $topRoutes,
            'requestSummary' => $requestSummary,
            'customRouteSummary' => $customRouteSummary,
            'aiSupportSummary' => $aiSupportSummary,
            'reliabilitySummary' => $reliabilitySummary,
            'thesisAlignment' => $thesisAlignment,
        ] = $this->sharedReportData(forExport: true);
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
                    // Route names are user-supplied and this file is opened in
                    // Excel/Sheets, where a leading = + - @ makes the cell a
                    // live formula. Neutralised below; ordinary names are
                    // written unchanged.
                    $this->csvSafe($row['route_name']),
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
        return view('admin.reports.pdf', $this->sharedReportData(forExport: true));
    }

    /**
     * Defuse CSV formula injection. A spreadsheet treats a cell beginning with
     * = + - @ (or a leading tab/CR) as a formula, so a route named
     * "=HYPERLINK(...)" would execute when an admin opens the export. Prefixing
     * a single quote makes the cell literal text; Excel and Sheets both hide
     * that prefix on display. Values not starting with those characters are
     * returned untouched, so normal exports are byte-identical.
     */
    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'" . $value : $value;
    }
}
