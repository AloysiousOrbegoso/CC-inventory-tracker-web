<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();
        $status = $request->query('status');
        $type = $request->query('type');

        $query = LeaveRequest::with(['user', 'branch', 'reviewer']);

        // Staff can only see their own requests
        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        } elseif ($isManager) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        $leaveRequests = $query->latest()->paginate(20);

        // Stats
        $statsQuery = LeaveRequest::query();
        if ($user->isStaff()) {
            $statsQuery->where('user_id', $user->id);
        } elseif ($isManager) {
            $statsQuery->where('branch_id', $user->branch_id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
        ];

        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('leave.index', [
            'leaveRequests' => $leaveRequests,
            'stats' => $stats,
            'status' => $status,
            'type' => $type,
            'branches' => $branches,
            'isManager' => $isManager,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'required|in:sick,vacation,personal,emergency,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('leave.index')
            ->with('success', 'Leave request submitted successfully.');
    }

    public function updateStatus(Request $request, LeaveRequest $leaveRequest)
    {
        $user = $request->user();

        // Only managers approve/reject leave — owner is an overseer
        if (! $user->isManager()) {
            return back()->withErrors(['error' => 'Forbidden. Managers handle leave requests.']);
        }

        if ($user->isManager() && $user->branch_id !== $leaveRequest->branch_id) {
            return back()->withErrors(['error' => 'Forbidden.']);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'reviewer_notes' => 'nullable|string|max:500',
        ]);

        $leaveRequest->update([
            'status' => $validated['status'],
            'reviewed_by' => $user->id,
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
        ]);

        return back()->with('success', 'Leave request ' . ucfirst($validated['status']) . '.');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        $user = request()->user();

        // Only the requester can delete their own pending request
        if ($leaveRequest->user_id !== $user->id || $leaveRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'Only your pending requests can be cancelled.']);
        }

        $leaveRequest->delete();

        return redirect()->route('leave.index')
            ->with('success', 'Leave request cancelled.');
    }
}
