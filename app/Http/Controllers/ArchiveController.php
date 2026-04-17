<?php

namespace App\Http\Controllers;

use App\Services\ArchiveService;
use App\Services\ArchivedPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function __construct(
        private readonly ArchiveService $archiveService,
        private readonly ArchivedPaymentService $archivedPaymentService
    )
    {
    }

    public function index(Request $request): View
    {
        $monthKey = $this->resolveMonthKey($request);

        $months = $this->archiveService->getMonthOptions();
        $summary = $this->archiveService->summaryForMonth($request->user(), $monthKey);

        return view('archive.index', compact('months', 'summary', 'monthKey'));
    }

    public function trips(Request $request): View
    {
        $monthKey = $this->resolveMonthKey($request);
        $months = $this->archiveService->getMonthOptions();
        $archivedTrips = $this->archiveService->paginateArchivedTrips($request->user(), $monthKey);

        return view('archive.trips', compact('months', 'archivedTrips', 'monthKey'));
    }

    public function payments(Request $request): View
    {
        $monthKey = $this->resolveMonthKey($request);
        $months = $this->archiveService->getArchivedPaymentMonthOptions($request->user());
        $summary = $monthKey
            ? $this->archiveService->summarizeArchivedPayments($request->user(), $monthKey)
            : null;
        $myPayments = $monthKey
            ? $this->archiveService->paginateArchivedPaymentsForUser($request->user(), $monthKey)
            : null;
        $driverPayments = $monthKey
            ? $this->archiveService->paginateArchivedPaymentsForDriver($request->user(), $monthKey)
            : null;
        $reminderState = $driverPayments
            ? $this->archivedPaymentService->reminderStateForPayments($driverPayments->getCollection())
            : [];

        return view('archive.payments', compact('months', 'summary', 'myPayments', 'driverPayments', 'monthKey', 'reminderState'));
    }

    private function resolveMonthKey(Request $request): ?string
    {
        $monthKey = $request->string('month')->toString();

        return preg_match('/^\d{4}-\d{2}$/', $monthKey) ? $monthKey : null;
    }
}
