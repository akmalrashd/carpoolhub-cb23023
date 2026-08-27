<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\SendAdminMessageRequest;
use App\Models\User;
use App\Services\AdminMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AdminMessageController extends Controller
{
    public function __construct(private readonly AdminMessageService $adminMessageService)
    {
    }

    public function create(): View
    {
        $roleCounts = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $totalUsers = (int) $roleCounts->sum();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.messages.create', compact('roleCounts', 'totalUsers', 'users'));
    }

    public function store(SendAdminMessageRequest $request): RedirectResponse
    {
        try {
            $count = $this->adminMessageService->send($request->user(), $request->validated());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('admin.messages.create')
            ->with('status', "Message sent to {$count} recipient(s).");
    }
}
