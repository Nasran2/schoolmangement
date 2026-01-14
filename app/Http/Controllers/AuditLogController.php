<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with(['user', 'auditable'])
            ->orderByDesc('created_at');

        if ($action = $request->string('action')->toString()) {
            $query->where('action', 'like', "%{$action}%");
        }
        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($from = $request->date('from')) {
            $query->where('created_at', '>=', $from->startOfDay());
        }
        if ($to = $request->date('to')) {
            $query->where('created_at', '<=', $to->endOfDay());
        }
        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('metadata', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,
            'filters' => [
                'action' => $action,
                'user_id' => $userId,
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'q' => $search,
            ],
        ]);
    }
}
