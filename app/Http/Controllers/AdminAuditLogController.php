<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function __construct(private readonly AdminAuditService $adminAuditService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'action' => ['nullable', 'string', 'max:100'],
            'admin_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $logs = $this->adminAuditService->paginateLogs($filters);
        $actionOptions = $this->adminAuditService->distinctActions();
        $adminOptions = $this->adminAuditService->adminsWithLogs();

        return view('admin.audit-log.index', compact('logs', 'filters', 'actionOptions', 'adminOptions'));
    }
}
