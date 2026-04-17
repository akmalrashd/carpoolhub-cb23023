<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillingCycle\CloseBillingCycleRequest;
use App\Models\BillingCycle;
use App\Services\BillingCycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class BillingCycleController extends Controller
{
    public function __construct(private readonly BillingCycleService $billingCycleService)
    {
    }

    public function index(): View
    {
        $viewer = request()->user();
        $openCycle = $this->billingCycleService->getOrCreateOpenCycle();
        $cycles = $this->billingCycleService->paginateCycles($viewer);
        $summaries = $this->billingCycleService->summarizeCycles($cycles->getCollection(), $viewer);
        $openSummary = $summaries[$openCycle->id] ?? $this->billingCycleService->summarizeCycle($openCycle, $viewer);
        $latestClosedCycle = $this->billingCycleService->latestClosedCycle();
        $canUndoLatestClose = $this->billingCycleService->canUndoLatestClose($latestClosedCycle);

        return view('billing-cycles.index', compact(
            'openCycle',
            'openSummary',
            'cycles',
            'summaries',
            'latestClosedCycle',
            'canUndoLatestClose',
        ));
    }

    public function close(CloseBillingCycleRequest $request, BillingCycle $billingCycle): RedirectResponse
    {
        try {
            $this->billingCycleService->closeCycle($request->user(), $billingCycle);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('billing-cycles.index')
            ->with('status', "Billing cycle {$billingCycle->month_key} closed and archived.");
    }

    public function undoLastClose(CloseBillingCycleRequest $request): RedirectResponse
    {
        try {
            $cycle = $this->billingCycleService->undoLatestClose($request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('billing-cycles.index')
            ->with('status', "Fallback completed. Cycle {$cycle->month_key} is open again.");
    }
}
