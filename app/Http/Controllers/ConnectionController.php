<?php

namespace App\Http\Controllers;

use App\Http\Requests\Connection\RespondConnectionRequest;
use App\Http\Requests\Connection\StoreConnectionRequest;
use App\Models\Connection;
use App\Models\User;
use App\Services\ConnectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConnectionController extends Controller
{
    public function __construct(
        private readonly ConnectionService $connectionService
    ) {
    }

    public function index(Request $request): View
    {
        $data = $this->connectionService->getIndexData(
            $request->user(),
            $request->string('q')->toString()
        );

        return view('connections.index', $data);
    }

    public function store(StoreConnectionRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $this->connectionService->sendRequest(
                $request->user(),
                (int) $request->validated('receiver_id')
            );
        } catch (ValidationException $exception) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors()
                ], 422);
            }
            return back()->withErrors($exception->errors())->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Connection request sent.'
            ]);
        }

        return redirect()
            ->route('connections.index')
            ->with('status', 'Connection request sent.');
    }

    public function respond(RespondConnectionRequest $request, Connection $connection): RedirectResponse
    {
        try {
            $this->connectionService->respond(
                $request->user(),
                $connection,
                $request->validated('action')
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $statusText = $request->validated('action') === 'accept' ? 'accepted' : 'rejected';

        return redirect()
            ->route('connections.index')
            ->with('status', "Connection request {$statusText}.");
    }

    public function cancel(Request $request, Connection $connection): RedirectResponse
    {
        try {
            $this->connectionService->cancel($request->user(), $connection);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('connections.index')
            ->with('status', 'Connection request cancelled.');
    }

    public function remove(Request $request, User $user): RedirectResponse
    {
        try {
            $this->connectionService->removeAccepted($request->user(), $user);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('connections.index')
            ->with('status', 'Connection removed.');
    }
}
