<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use App\Services\WithdrawalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly WithdrawalService $withdrawalService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('wallet.index', [
            'summary' => $this->walletService->summaryFor($user),
            'transactions' => $this->walletService->paginateTransactions($user, 20),
            'withdrawals' => $this->withdrawalService->paginateForDriver($user, 10),
            'minWithdrawalAmount' => (float) (\App\Models\SystemSetting::get('wallet_min_withdrawal_amount') ?? 10),
        ]);
    }

    public function requestWithdrawal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->withdrawalService->request($request->user(), (float) $validated['amount']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('wallet.index')->with('status', 'Withdrawal request submitted — an admin will review it shortly.');
    }
}
