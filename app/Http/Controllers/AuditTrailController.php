<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditTrailController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();

        $query = AuditLog::with('user');

        // Filter by model type
        $modelType = $request->query('model');
        if ($modelType) {
            $query->where('model_type', $modelType);
        }

        // Filter by action
        $action = $request->query('action');
        if ($action && $action !== 'all') {
            $query->where('action', $action);
        }

        // Filter by user
        $userId = $request->query('user_id');
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Date range
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $auditLogs = $query->latest()->paginate(50);

        // Get unique model types for filter
        $modelTypes = AuditLog::distinct()->pluck('model_type')->sort()->toArray();

        // Get users for filter
        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('audit.index', [
            'auditLogs' => $auditLogs,
            'modelTypes' => $modelTypes,
            'users' => $users,
            'selectedModel' => $modelType,
            'selectedAction' => $action,
            'selectedUserId' => $userId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('audit.show', [
            'log' => $auditLog,
        ]);
    }
}
