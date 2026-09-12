<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminWithdrawalController extends Controller
{
    public function __construct(private readonly WithdrawalService $withdrawalService) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,paid,rejected'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);
        $filters['status'] = $filters['status'] ?? 'pending';

        return view('admin.withdrawals.index', [
            'withdrawals' => $this->withdrawalService->paginateForAdmin($filters, 15),
            'filters' => $filters,
        ]);
    }

    public function markPaid(Request $request, WithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->withdrawalService->markPaid($request->user(), $withdrawalRequest, $validated['admin_remarks'] ?? null);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', "Withdrawal #{$withdrawalRequest->id} marked as paid.");
    }

    public function reject(Request $request, WithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->withdrawalService->reject($request->user(), $withdrawalRequest, $validated['reason']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', "Withdrawal #{$withdrawalRequest->id} rejected and refunded to the driver's wallet.");
    }
}
