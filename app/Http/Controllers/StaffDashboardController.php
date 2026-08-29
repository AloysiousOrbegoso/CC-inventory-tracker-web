<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\DiscrepancyAlert;
use App\Models\Ingredient;
use App\Models\ShiftLog;
use App\Models\ShiftStockCount;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $branch = Branch::find($branchId);

        $openShift = ShiftLog::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('shift_start')
            ->first();

        $hasVerifiedStock = $openShift
            && ShiftStockCount::where('shift_log_id', $openShift->id)->exists();

        $totalStaff = User::where('branch_id', $branchId)->where('role', User::ROLE_STAFF)->count();
        $clockedIn = ShiftLog::where('branch_id', $branchId)
            ->where('status', 'open')
            ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_STAFF))
            ->distinct('user_id')
            ->count('user_id');

        $transactionsToday = Transaction::join('products', 'products.id', '=', 'transactions.product_id')
            ->where('transactions.branch_id', $branchId)
            ->whereDate('transactions.created_at', today())
            ->selectRaw('products.category, COUNT(*) as count')
            ->groupBy('products.category')
            ->pluck('count', 'category');

        $stock = BranchStock::with('ingredient')
            ->where('branch_id', $branchId)
            ->get();
        $lowStock = $stock->filter(fn ($s) => $s->min_threshold > 0 && $s->current_quantity <= $s->min_threshold);

        // What "Close" needs to collect a physical count for — the same
        // ingredient set "Verify Stock" opened the shift against.
        $closingCandidates = $openShift
            ? ShiftStockCount::with('ingredient')->where('shift_log_id', $openShift->id)->get()
            : collect();

        return view('staff.dashboard', [
            'staffUser' => $user,
            'branch' => $branch,
            'openShift' => $openShift,
            'hasVerifiedStock' => $hasVerifiedStock,
            'hasVerifiedTill' => $openShift && $openShift->opening_till_amount !== null,
            'hasCompletedPrep' => $openShift && $openShift->prep_completed_at !== null,
            'hasCompletedClean' => $openShift && $openShift->cleaning_completed_at !== null,
            'totalStaff' => $totalStaff,
            'clockedIn' => $clockedIn,
            'transactionsToday' => $transactionsToday,
            'totalIngredients' => $stock->count(),
            'lowStockIngredients' => $lowStock->values(),
            'closingCandidates' => $closingCandidates,
        ]);
    }

    /**
     * Self-service clock-in — opens a shift for the logged-in staff member.
     */
    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user();

        $existing = ShiftLog::where('user_id', $user->id)->where('status', 'open')->first();
        if (! $existing) {
            ShiftLog::create([
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'shift_start' => now(),
                'status' => 'open',
            ]);
        }

        return redirect()->route('staff.dashboard')->with('status', 'Shift opened.');
    }

    /**
     * Self-service clock-out — closes the logged-in staff member's open shift.
     */
    public function clockOut(Request $request): RedirectResponse
    {
        $user = $request->user();

        $openShift = ShiftLog::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('shift_start')
            ->first();

        if ($openShift) {
            $openShift->update(['shift_end' => now(), 'status' => 'closed']);
        }

        return redirect()->route('login')->with('status', 'Clocked out. See you next shift!');
    }

    /**
     * Marks the "Verify Stock" pre-opening task done by recording a stock
     * count snapshot for every branch ingredient against the open shift.
     */
    public function verifyStock(Request $request): RedirectResponse
    {
        $user = $request->user();

        $openShift = ShiftLog::where('user_id', $user->id)->where('status', 'open')->latest('shift_start')->first();
        if (! $openShift) {
            $openShift = ShiftLog::create([
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'shift_start' => now(),
                'status' => 'open',
            ]);
        }

        $stock = BranchStock::where('branch_id', $user->branch_id)->get();
        foreach ($stock as $s) {
            ShiftStockCount::updateOrCreate(
                ['shift_log_id' => $openShift->id, 'ingredient_id' => $s->ingredient_id],
                ['opening_quantity' => $s->current_quantity]
            );
        }

        return redirect()->route('staff.dashboard')->with('status', 'Stock verified for this shift.');
    }

    /**
     * Records the starting cash-drawer count for the open shift.
     */
    public function verifyTill(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'till_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $openShift = ShiftLog::where('user_id', $user->id)->where('status', 'open')->latest('shift_start')->first();

        if (! $openShift) {
            return redirect()->route('staff.dashboard')->with('status', 'Open your shift first.');
        }

        $openShift->update(['opening_till_amount' => $validated['till_amount']]);

        return redirect()->route('staff.dashboard')->with('status', 'Till amount verified.');
    }

    /**
     * Marks the "Prep and Set-up" pre-opening task done for the open shift.
     */
    public function markPrepDone(Request $request): RedirectResponse
    {
        $user = $request->user();

        $openShift = ShiftLog::where('user_id', $user->id)->where('status', 'open')->latest('shift_start')->first();

        if (! $openShift) {
            return redirect()->route('staff.dashboard')->with('status', 'Open your shift first.');
        }

        $openShift->update(['prep_completed_at' => now()]);

        return redirect()->route('staff.dashboard')->with('status', 'Prep and set-up marked done.');
    }

    /**
     * Marks the "Clean" task done for the open shift.
     */
    public function markCleanDone(Request $request): RedirectResponse
    {
        $user = $request->user();

        $openShift = ShiftLog::where('user_id', $user->id)->where('status', 'open')->latest('shift_start')->first();

        if (! $openShift) {
            return redirect()->route('staff.dashboard')->with('status', 'Open your shift first.');
        }

        $openShift->update(['cleaning_completed_at' => now()]);

        return redirect()->route('staff.dashboard')->with('status', 'Cleaning marked done.');
    }

    /**
     * The real end-of-shift reconciliation step — mirrors
     * Api\ShiftController::close's variance/threshold logic exactly, just
     * over session auth instead of a JWT so the staff dashboard (a
     * session-authenticated page) can call it directly. "Clock Out"
     * force-closes the shift status with no physical count at all; this
     * is what actually records the closing count, detects variance
     * against the configured threshold, and corrects BranchStock to the
     * physical number.
     */
    public function closeShift(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'closing_counts' => ['required', 'array', 'min:1'],
            'closing_counts.*.ingredient_id' => ['required', 'exists:ingredients,id'],
            'closing_counts.*.closing_quantity_actual' => ['required', 'numeric', 'min:0'],
        ]);

        $shiftLog = ShiftLog::where('user_id', $user->id)->where('status', 'open')->latest('shift_start')->first();

        if (! $shiftLog) {
            return response()->json(['message' => 'No open shift to close.'], 422);
        }

        return DB::transaction(function () use ($validated, $shiftLog, $user) {
            $alerts = [];

            foreach ($validated['closing_counts'] as $count) {
                $stockCount = ShiftStockCount::where('shift_log_id', $shiftLog->id)
                    ->where('ingredient_id', $count['ingredient_id'])
                    ->first();

                if (! $stockCount) {
                    continue;
                }

                $branchStock = BranchStock::where('branch_id', $shiftLog->branch_id)
                    ->where('ingredient_id', $count['ingredient_id'])
                    ->first();

                $expected = $branchStock->current_quantity ?? $stockCount->opening_quantity;
                $actual = $count['closing_quantity_actual'];
                $variance = $actual - $expected;

                $stockCount->update([
                    'closing_quantity_expected' => $expected,
                    'closing_quantity_actual' => $actual,
                    'variance' => $variance,
                ]);

                if (abs($variance) > 0) {
                    $thresholdPct = (float) AppSetting::get('variance_threshold_pct', 0.05);
                    $thresholdPhp = (float) AppSetting::get('variance_threshold_php', 100);

                    $pct = ((float) $expected) !== 0.0 ? abs($variance) / abs($expected) : 1.0;
                    $unitCost = (float) (Ingredient::find($count['ingredient_id'])?->primarySupplier()?->pivot?->unit_cost ?? 0);
                    $phpImpact = abs($variance) * $unitCost;

                    if ($pct >= $thresholdPct || $phpImpact >= $thresholdPhp) {
                        $severity = ($pct >= $thresholdPct * 2 || $phpImpact >= $thresholdPhp * 2) ? 'high' : 'medium';

                        $alerts[] = DiscrepancyAlert::create([
                            'branch_id' => $shiftLog->branch_id,
                            'type' => 'shift_variance',
                            'severity' => $severity,
                            'ingredient_id' => $count['ingredient_id'],
                            'shift_log_id' => $shiftLog->id,
                            'expected_value' => $expected,
                            'actual_value' => $actual,
                            'variance' => $variance,
                            'details' => "Shift-end variance of {$variance} detected for ingredient #{$count['ingredient_id']} during shift #{$shiftLog->id}.",
                            'status' => 'pending',
                        ]);
                    }
                }

                if ($branchStock) {
                    $before = $branchStock->current_quantity;

                    $branchStock->update([
                        'current_quantity' => $actual,
                        'last_updated_at' => now(),
                    ]);

                    StockMovement::create([
                        'branch_stock_id' => $branchStock->id,
                        'type' => StockMovement::TYPE_SHIFT_CORRECTION,
                        'quantity_change' => $actual - $before,
                        'quantity_before' => $before,
                        'quantity_after' => $actual,
                        'reference_type' => ShiftLog::class,
                        'reference_id' => $shiftLog->id,
                        'user_id' => $user->id,
                        'notes' => "Physical count at shift close (variance {$variance}).",
                    ]);
                }
            }

            $shiftLog->update(['shift_end' => now(), 'status' => 'closed']);

            return response()->json([
                'success' => true,
                'message' => $alerts ? count($alerts).' discrepancy alert(s) raised — the owner/manager has been notified.' : 'Shift closed, no discrepancies found.',
                'discrepancy_alerts' => $alerts,
            ]);
        });
    }
}
