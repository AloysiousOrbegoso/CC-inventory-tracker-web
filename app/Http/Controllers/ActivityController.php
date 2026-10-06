<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Return a worker's recent transactions, shift summaries, and
     * stock discrepancies attributed to their shifts.
     */
    public function __invoke(Request $request, User $user): JsonResponse
    {
        $this->authorize('viewActivity', $user);

        $type = $request->query('type', 'transactions');

        return match ($type) {
            'shifts'        => $this->shifts($user),
            'discrepancies' => $this->discrepancies($user),
            default         => $this->transactions($user),
        };
    }

    private function transactions(User $user): JsonResponse
    {
        $transactions = $user->transactions()
            ->with('product', 'branch')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($t) => [
                'id'           => $t->id,
                'product'      => $t->product?->name ?? 'Unknown',
                'quantity'     => (int) $t->quantity,
                'total'        => (float) $t->total_amount,
                'branch'       => $t->branch?->name ?? '—',
                'date'         => $t->created_at->format('M d, Y g:i A'),
                'sync_status'  => $t->sync_status,
            ]);

        return response()->json(['data' => $transactions]);
    }

    private function shifts(User $user): JsonResponse
    {
        $shifts = $user->shiftLogs()
            ->with('branch')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($s) => [
                'id'       => $s->id,
                'date'     => $s->shift_start->format('M d, Y'),
                'clock_in' => $s->shift_start->format('g:i A'),
                'clock_out'=> $s->shift_end?->format('g:i A') ?? '—',
                'duration' => $s->shift_end
                    ? $s->shift_start->diffForHumans($s->shift_end, true)
                    : 'Ongoing',
                'status'   => $s->status,
                'branch'   => $s->branch?->name ?? '—',
            ]);

        return response()->json(['data' => $shifts]);
    }

    private function discrepancies(User $user): JsonResponse
    {
        // Discrepancies from this worker's shift logs
        $discrepancies = $user->shiftLogs()
            ->with('discrepancyAlerts.ingredient', 'discrepancyAlerts.branch')
            ->whereHas('discrepancyAlerts')
            ->latest()
            ->take(20)
            ->get()
            ->flatMap(fn ($shift) => $shift->discrepancyAlerts->map(fn ($d) => [
                'id'            => $d->id,
                'date'          => $d->created_at->format('M d, Y'),
                'ingredient'    => $d->ingredient?->name ?? 'Unknown',
                'expected'      => (float) $d->expected_value,
                'actual'        => (float) $d->actual_value,
                'variance'      => (float) $d->variance,
                'severity'      => $d->severity ?? 'mild',
                'branch'        => $d->branch?->name ?? '—',
            ]))
            ->sortByDesc('id')
            ->take(20)
            ->values();

        return response()->json(['data' => $discrepancies]);
    }
}
