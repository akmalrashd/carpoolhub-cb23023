<?php

namespace App\Http\Controllers;

use App\Models\AdminActionLog;
use App\Models\TripCancellationLog;
use App\Models\TripPaymentStatusLog;
use App\Services\AdminAuditService;
use App\Services\PaymentService;
use App\Services\TripService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    private const VIEWS = ['admin', 'payments', 'cancellations'];

    public function __construct(
        private readonly AdminAuditService $adminAuditService,
        private readonly PaymentService $paymentService,
        private readonly TripService $tripService,
    ) {}

    /**
     * Three tabs sharing one page/URL (?view=admin|payments|cancellations) —
     * admin_action_logs (accountability among admins), trip_payment_status_logs
     * and trip_cancellation_logs (the dispute-evidence trail added alongside
     * PaymentService/TripService). Kept as tabs on the existing Audit Log page
     * rather than three separate admin pages, same reasoning as the rest of
     * admin: one more full page per dataset is the clutter this app already
     * moved away from.
     */
    public function index(Request $request): View
    {
        $view = in_array($request->query('view'), self::VIEWS, true) ? $request->query('view') : 'admin';

        $sharedFilters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $actionOptions = collect();
        $adminOptions = collect();
        $stats = [];

        if ($view === 'payments') {
            $filters = $sharedFilters + $request->validate([
                'to_status' => ['nullable', 'in:unpaid,pending_confirmation,paid'],
            ]);
            $logs = $this->paymentService->paginatePaymentStatusLogs($filters);
            $stats = [
                'total' => TripPaymentStatusLog::query()->count(),
                'today' => TripPaymentStatusLog::query()->whereDate('created_at', today())->count(),
                'marked_paid' => TripPaymentStatusLog::query()->where('to_status', 'paid')->count(),
                'rejected_or_reversed' => TripPaymentStatusLog::query()->where('to_status', 'unpaid')->whereNotNull('reason')->count(),
            ];
        } elseif ($view === 'cancellations') {
            $filters = $sharedFilters;
            $logs = $this->tripService->paginateCancellationLogs($filters);
            $stats = [
                'total' => TripCancellationLog::query()->count(),
                'today' => TripCancellationLog::query()->whereDate('created_at', today())->count(),
                'with_reason' => TripCancellationLog::query()->whereNotNull('reason')->count(),
                'distinct_drivers' => TripCancellationLog::query()->distinct('driver_id')->count('driver_id'),
            ];
        } else {
            $filters = $sharedFilters + $request->validate([
                'action' => ['nullable', 'string', 'max:100'],
                'admin_id' => ['nullable', 'integer'],
            ]);
            $logs = $this->adminAuditService->paginateLogs($filters);
            $actionOptions = $this->adminAuditService->distinctActions();
            $adminOptions = $this->adminAuditService->adminsWithLogs();
        }

        $viewCounts = [
            'admin' => AdminActionLog::query()->count(),
            'payments' => TripPaymentStatusLog::query()->count(),
            'cancellations' => TripCancellationLog::query()->count(),
        ];

        return view('admin.audit-log.index', compact('view', 'logs', 'filters', 'actionOptions', 'adminOptions', 'viewCounts', 'stats'));
    }
}
