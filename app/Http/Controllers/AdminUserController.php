<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\RejectDriverRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $adminUserService)
    {
    }

    public function index(Request $request): View
    {
        $users = $this->adminUserService->paginateUsers(
            $request->string('q')->toString(),
            $request->string('role')->toString(),
            $request->string('active')->toString()
        );

        $pendingDrivers = $this->adminUserService->paginatePendingDrivers(10);

        return view('admin.users.index', compact('users', 'pendingDrivers'));
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->adminUserService->updateUser($request->user(), $user, $request->validated());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', "User {$user->name} updated.");
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        try {
            $this->adminUserService->approveDriver($request->user(), $user);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', "{$user->name}'s driver application was approved.");
    }

    public function reject(RejectDriverRequest $request, User $user): RedirectResponse
    {
        try {
            $this->adminUserService->rejectDriver($request->user(), $user, $request->validated()['reason']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', "{$user->name}'s driver application was rejected.");
    }
}

