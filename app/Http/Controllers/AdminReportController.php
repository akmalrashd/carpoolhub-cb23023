<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    /**
     * The ReportService calls every admin report view needs. Was copied
     * into index(), exportExcel() and exportPdfView() separately — a metric
     * added to the report page had to be remembered in three places to also
     * reach the exports. $forExport switches monthlyReports to the export's
     * longer 24-month window; everything else is identical either way.
     * $dateFrom/$dateTo scope only overview() — see ReportService::overview()
     * for why the rest of these stay all-time.
     */
    private function sharedReportData(bool $forExport = false, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return [
            'overview' => $this->reportService->overview($dateFrom, $dateTo),
            'paymentBreakdown' => $this->reportService->paymentStatusBreakdown(),
            'monthlyReports' => $forExport
                ? $this->reportService->monthlyTripSummaryForExport()
                : $this->reportService->monthlyTripSummary(),
            'topRoutes' => $this->reportService->topRoutes(),
            'topDrivers' => $this->reportService->topDrivers(),
            'requestSummary' => $this->reportService->requestDecisionSummary(),
            'customRouteSummary' => $this->reportService->customRouteSummary(),
            'aiSupportSummary' => $this->reportService->aiSupportSummary(),
            'aiUsage' => $this->reportService->aiUsageSummary(),
            'reliabilitySummary' => $this->reportService->passengerReliabilitySummary(),
            'thesisAlignment' => $this->reportService->thesisAlignmentSummary(),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $data = $this->sharedReportData(false, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $data['dailyTripRanges'] = $this->reportService->dailyTripRanges();
        $data['dateFrom'] = $filters['date_from'] ?? null;
        $data['dateTo'] = $filters['date_to'] ?? null;

        return view('admin.reports.index', $data);
    }

    /**
     * One real worksheet per dataset — Power BI (and Excel) treat each sheet
     * as its own table, so every sheet here is a plain rectangular grid:
     * headers as column titles in row 1, one record per row after that.
     * KPI "snapshot" groups (Overview, Passenger Requests, etc.) that used to
     * be dumped as vertical key/value pairs are written as a single wide row
     * instead — still one column per metric, just laid out horizontally.
     */
    public function exportExcel(): StreamedResponse
    {
        [
            'overview' => $overview,
            'paymentBreakdown' => $paymentBreakdown,
            'monthlyReports' => $monthlyReports,
            'topRoutes' => $topRoutes,
            'topDrivers' => $topDrivers,
            'requestSummary' => $requestSummary,
            'customRouteSummary' => $customRouteSummary,
            'aiSupportSummary' => $aiSupportSummary,
            'aiUsage' => $aiUsage,
            'reliabilitySummary' => $reliabilitySummary,
            'thesisAlignment' => $thesisAlignment,
        ] = $this->sharedReportData(forExport: true);
        $dailyTripRanges = $this->reportService->dailyTripRanges();

        $paymentBreakdownRows = collect($paymentBreakdown)
            ->map(fn (array $row, string $status) => [
                'status' => ucfirst(str_replace('_', ' ', $status)),
                'count' => $row['count'],
                'amount' => $row['amount'],
            ])
            ->values()->all();

        $dailyTripRows = collect($dailyTripRanges['30d'] ?? [])
            ->map(fn (int $count, string $date) => ['date' => $date, 'trips' => $count])
            ->values()->all();

        $byEndpointRows = collect($aiUsage['by_endpoint'] ?? [])
            ->map(fn (int $count, string $endpoint) => ['endpoint' => $endpoint, 'count' => $count])
            ->values()->all();

        $errorBreakdownRows = collect($aiUsage['error_breakdown'] ?? [])
            ->map(fn (int $count, ?string $type) => ['type' => $type ?: 'Unknown', 'count' => $count])
            ->values()->all();

        // [sheet title, columns, rows] — columns: header label, source key, cell type.
        $sheets = [
            ['Report Info', [
                ['header' => 'Field', 'key' => 'field', 'type' => 'string'],
                ['header' => 'Value', 'key' => 'value', 'type' => 'string'],
            ], [
                ['field' => 'Report', 'value' => 'CarpoolHub Admin Report'],
                ['field' => 'Generated At', 'value' => now()->toDateTimeString()],
                ['field' => 'Data Scope', 'value' => 'All-time (the live Reports page can date-filter the Overview KPIs; every export is always all-time)'],
            ]],
            ['Overview', [
                ['header' => 'Users Total', 'key' => 'users_total', 'type' => 'int'],
                ['header' => 'Drivers Total', 'key' => 'drivers_total', 'type' => 'int'],
                ['header' => 'Passengers Total', 'key' => 'passengers_total', 'type' => 'int'],
                ['header' => 'Active Users Total', 'key' => 'active_users_total', 'type' => 'int'],
                ['header' => 'Trips Total', 'key' => 'trips_total', 'type' => 'int'],
                ['header' => 'Trips Completed', 'key' => 'trips_completed', 'type' => 'int'],
                ['header' => 'Fare Total (RM)', 'key' => 'fare_total', 'type' => 'money'],
                ['header' => 'Payments Total (RM)', 'key' => 'payments_total', 'type' => 'money'],
                ['header' => 'Payments Paid (RM)', 'key' => 'payments_paid', 'type' => 'money'],
                ['header' => 'Payments Pending/Unpaid (RM)', 'key' => 'payments_pending_unpaid', 'type' => 'money'],
                ['header' => 'Public Trips Total', 'key' => 'public_trips_total', 'type' => 'int'],
                ['header' => 'Custom Route Requests Total', 'key' => 'custom_route_requests_total', 'type' => 'int'],
                ['header' => 'Join Requests Total', 'key' => 'join_requests_total', 'type' => 'int'],
            ], [$overview]],
            ['Payment Breakdown', [
                ['header' => 'Status', 'key' => 'status', 'type' => 'string'],
                ['header' => 'Count', 'key' => 'count', 'type' => 'int'],
                ['header' => 'Amount (RM)', 'key' => 'amount', 'type' => 'money'],
            ], $paymentBreakdownRows],
            ['Top Routes', [
                ['header' => 'Route', 'key' => 'route_name', 'type' => 'string'],
                ['header' => 'Trips', 'key' => 'trip_count', 'type' => 'int'],
                ['header' => 'Avg Fare (RM)', 'key' => 'avg_fare', 'type' => 'money'],
                ['header' => 'Drivers', 'key' => 'driver_count', 'type' => 'int'],
                ['header' => 'Fare Total (RM)', 'key' => 'fare_total', 'type' => 'money'],
            ], $topRoutes],
            ['Top Drivers', [
                ['header' => 'Driver', 'key' => 'driver_name', 'type' => 'string'],
                ['header' => 'Trips', 'key' => 'trip_count', 'type' => 'int'],
                ['header' => 'Completed', 'key' => 'completed_count', 'type' => 'int'],
                ['header' => 'Completion Rate (%)', 'key' => 'completion_rate', 'type' => 'percent'],
                ['header' => 'Distinct Routes', 'key' => 'route_count', 'type' => 'int'],
                ['header' => 'Avg Fare (RM)', 'key' => 'avg_fare', 'type' => 'money'],
                ['header' => 'Total Revenue (RM)', 'key' => 'fare_total', 'type' => 'money'],
            ], $topDrivers],
            ['Monthly Summary', [
                ['header' => 'Month', 'key' => 'month_key', 'type' => 'string'],
                ['header' => 'Trips', 'key' => 'trip_count', 'type' => 'int'],
                ['header' => 'New Users', 'key' => 'new_users', 'type' => 'int'],
                ['header' => 'Fare Total (RM)', 'key' => 'fare_total', 'type' => 'money'],
                ['header' => 'Paid Total (RM)', 'key' => 'paid_total', 'type' => 'money'],
                ['header' => 'Pending/Unpaid Total (RM)', 'key' => 'pending_unpaid_total', 'type' => 'money'],
            ], $monthlyReports],
            ['Trips By Day', [
                ['header' => 'Date', 'key' => 'date', 'type' => 'string'],
                ['header' => 'Trips', 'key' => 'trips', 'type' => 'int'],
            ], $dailyTripRows],
            ['Passenger Requests', [
                ['header' => 'Total', 'key' => 'total', 'type' => 'int'],
                ['header' => 'Pending', 'key' => 'pending', 'type' => 'int'],
                ['header' => 'Approved', 'key' => 'approved', 'type' => 'int'],
                ['header' => 'Rejected', 'key' => 'rejected', 'type' => 'int'],
                ['header' => 'Cancelled', 'key' => 'cancelled', 'type' => 'int'],
                ['header' => 'Approval Rate (%)', 'key' => 'approval_rate', 'type' => 'percent'],
                ['header' => 'Cancellation Rate (%)', 'key' => 'cancellation_rate', 'type' => 'percent'],
                ['header' => 'Avg Decision Minutes', 'key' => 'avg_decision_minutes', 'type' => 'decimal'],
            ], [$requestSummary]],
            ['Custom Route Preference', [
                ['header' => 'Total Route Points', 'key' => 'total_route_points', 'type' => 'int'],
                ['header' => 'Custom Requests', 'key' => 'custom_requests', 'type' => 'int'],
                ['header' => 'Accepted Custom Requests', 'key' => 'accepted_custom_requests', 'type' => 'int'],
                ['header' => 'Custom Share (%)', 'key' => 'custom_share', 'type' => 'percent'],
                ['header' => 'Avg Detour (KM)', 'key' => 'avg_detour_km', 'type' => 'decimal'],
                ['header' => 'Avg Extra Fee (RM)', 'key' => 'avg_extra_fee', 'type' => 'money'],
                ['header' => 'Extra Fee Total (RM)', 'key' => 'extra_fee_total', 'type' => 'money'],
            ], [$customRouteSummary]],
            ['AI Decision Support', [
                ['header' => 'AI Interactions', 'key' => 'recommendation_logs', 'type' => 'int'],
                ['header' => 'Avg Match Score (%)', 'key' => 'avg_match_score', 'type' => 'percent'],
                ['header' => 'Match Score Measured', 'key' => 'avg_match_score_measured', 'type' => 'bool'],
                ['header' => 'Strategy Suggestions', 'key' => 'strategy_suggestions', 'type' => 'int'],
            ], [$aiSupportSummary]],
            ['Passenger Reliability', [
                ['header' => 'Profiles Total', 'key' => 'profiles_total', 'type' => 'int'],
                ['header' => 'High Risk Total', 'key' => 'high_risk_total', 'type' => 'int'],
                ['header' => 'Avg Risk Score', 'key' => 'avg_risk_score', 'type' => 'decimal'],
                ['header' => 'Avg Payment Reliability (%)', 'key' => 'avg_payment_reliability', 'type' => 'percent'],
                ['header' => 'Total Payments', 'key' => 'total_payments', 'type' => 'int'],
                ['header' => 'Paid Payments', 'key' => 'paid_payments', 'type' => 'int'],
                ['header' => 'Unpaid Payments', 'key' => 'unpaid_payments', 'type' => 'int'],
                ['header' => 'Outstanding Amount (RM)', 'key' => 'outstanding_amount', 'type' => 'money'],
            ], [$reliabilitySummary]],
            ['AI Usage Summary', [
                ['header' => 'Total Calls', 'key' => 'total_calls', 'type' => 'int'],
                ['header' => 'Success Rate (%)', 'key' => 'success_rate', 'type' => 'percent'],
                ['header' => 'Total Input Tokens', 'key' => 'total_input_tokens', 'type' => 'int'],
                ['header' => 'Total Output Tokens', 'key' => 'total_output_tokens', 'type' => 'int'],
                ['header' => 'Retry Count', 'key' => 'retry_count', 'type' => 'int'],
            ], [$aiUsage]],
            ['AI Usage By Endpoint', [
                ['header' => 'Endpoint', 'key' => 'endpoint', 'type' => 'string'],
                ['header' => 'Calls', 'key' => 'count', 'type' => 'int'],
            ], $byEndpointRows],
            ['AI Usage Errors', [
                ['header' => 'Error Type', 'key' => 'type', 'type' => 'string'],
                ['header' => 'Count', 'key' => 'count', 'type' => 'int'],
            ], $errorBreakdownRows],
            ['Thesis Module Evidence', [
                ['header' => 'Objective', 'key' => 'objective', 'type' => 'string'],
                ['header' => 'Evidence', 'key' => 'evidence', 'type' => 'int'],
                ['header' => 'Unit', 'key' => 'unit', 'type' => 'string'],
            ], $thesisAlignment],
        ];

        $spreadsheet = new Spreadsheet;
        foreach ($sheets as $i => [$title, $columns, $rows]) {
            $sheet = $i === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($title);
            $this->writeReportSheet($sheet, $columns, $rows);
        }
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'carpoolhub-admin-report-'.now()->format('Ymd-His').'.xlsx';
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Cache-Control' => 'max-age=0',
        ];

        return response()->stream(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 200, $headers);
    }

    /**
     * Writes one column-headers-then-records grid onto a worksheet. Text
     * columns use setCellValueExplicit(..., TYPE_STRING) rather than the
     * auto-detecting setCellValue() — a route/driver name starting with
     * = + - @ would otherwise be stored as a live formula when the workbook
     * is opened (the XLSX equivalent of CSV formula injection).
     */
    private function writeReportSheet(Worksheet $sheet, array $columns, array $rows): void
    {
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));

        foreach ($columns as $i => $column) {
            $coord = Coordinate::stringFromColumnIndex($i + 1).'1';
            $sheet->setCellValueExplicit($coord, $column['header'], DataType::TYPE_STRING);
        }
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('2A1E04');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FACC15');

        foreach ($rows as $r => $row) {
            $excelRow = $r + 2;
            foreach ($columns as $i => $column) {
                $coord = Coordinate::stringFromColumnIndex($i + 1).$excelRow;
                $value = $row[$column['key']] ?? null;

                match ($column['type']) {
                    'string' => $sheet->setCellValueExplicit($coord, (string) ($value ?? ''), DataType::TYPE_STRING),
                    'bool' => $sheet->setCellValueExplicit($coord, $value ? 'Yes' : 'No', DataType::TYPE_STRING),
                    default => $sheet->setCellValue($coord, is_numeric($value) ? (float) $value : 0),
                };

                $format = match ($column['type']) {
                    'money', 'decimal' => '#,##0.00',
                    'percent' => '0.0"%"',
                    'int' => '#,##0',
                    default => null,
                };
                if ($format !== null) {
                    $sheet->getStyle($coord)->getNumberFormat()->setFormatCode($format);
                }
            }
        }

        foreach (range(1, count($columns)) as $i) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sheet->freezePane('A2');
    }

    public function exportPdfView(): View|Factory|Application
    {
        $data = $this->sharedReportData(forExport: true);
        $data['dailyTripRanges'] = $this->reportService->dailyTripRanges();

        return view('admin.reports.pdf', $data);
    }
}
