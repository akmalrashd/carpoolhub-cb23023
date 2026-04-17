<?php

namespace App\Http\Controllers;

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

        return view('admin.users.index', compact('users'));
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
}

