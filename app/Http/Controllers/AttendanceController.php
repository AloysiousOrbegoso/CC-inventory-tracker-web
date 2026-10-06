<?php

namespace App\Http\Controllers;

use App\Models\ShiftLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Clock a worker in (creates an open ShiftLog).
     */
    public function clockIn(Request $request, User $user): JsonResponse
    {
        if (! in_array($user->role, [User::ROLE_STAFF, User::ROLE_MANAGER])) {
            return response()->json(['message' => 'Worker not found.'], 404);
        }

        $this->authorize('manageWorker', $user);

        // Check for existing open shift
        $openShift = ShiftLog::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($openShift) {
            return response()->json([
                'message' => "{$user->name} already has an open shift.",
                'shift'   => $openShift,
            ], 422);
        }

        $shiftLog = ShiftLog::create([
            'branch_id'   => $user->branch_id,
            'user_id'     => $user->id,
            'shift_start' => now(),
            'status'      => 'open',
        ]);

        return response()->json([
            'message' => "{$user->name} clocked in successfully.",
            'shift'   => $shiftLog->load('user'),
        ], 201);
    }

    /**
     * Clock a worker out (closes the latest open ShiftLog).
     */
    public function clockOut(Request $request, User $user): JsonResponse
    {
        if (! in_array($user->role, [User::ROLE_STAFF, User::ROLE_MANAGER])) {
            return response()->json(['message' => 'Worker not found.'], 404);
        }

        $this->authorize('manageWorker', $user);

        $openShift = ShiftLog::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $openShift) {
            return response()->json(['message' => "{$user->name} does not have an open shift."], 404);
        }

        $openShift->update([
            'shift_end' => now(),
            'status'    => 'closed',
        ]);

        return response()->json([
            'message' => "{$user->name} clocked out successfully.",
            'shift'   => $openShift->fresh()->load('user'),
        ]);
    }

    /**
     * Get attendance history for a worker (last 30 shifts).
     */
    public function history(Request $request, User $user): JsonResponse
    {
        if (! in_array($user->role, [User::ROLE_STAFF, User::ROLE_MANAGER])) {
            return response()->json(['message' => 'Worker not found.'], 404);
        }

        // Gap fix: this read endpoint previously had no authorization check at
        // all — any authenticated staff account could enumerate coworkers'
        // shifts. Same rule as the write endpoints: owner/manager only.
        $this->authorize('manageWorker', $user);

        $shifts = ShiftLog::where('user_id', $user->id)
            ->latest('shift_start')
            ->take(30)
            ->get()
            ->map(fn ($s) => [
                'id'          => $s->id,
                'date'        => $s->shift_start?->format('M d, Y'),
                'clock_in'    => $s->shift_start?->format('g:ia'),
                'clock_out'   => $s->shift_end?->format('g:ia'),
                'duration'    => $s->shift_start && $s->shift_end
                    ? round(($s->shift_end->diffInMinutes($s->shift_start) / 60), 1) . 'h'
                    : ($s->status === 'open' ? 'In progress' : '—'),
                'status'      => $s->status,
            ]);

        return response()->json([
            'shifts' => $shifts,
        ]);
    }
}
