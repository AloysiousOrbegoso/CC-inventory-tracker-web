<?php

use App\Http\Controllers\AlertsController;
use App\Http\Controllers\AuthOnboardingController;
use App\Http\Controllers\BranchesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HiringController;
use App\Http\Controllers\LegalPapersController;
use App\Http\Controllers\NoticesController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\InventoryIntelligenceController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ForecastingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\CustomReportController;
use App\Http\Controllers\SafetyChecklistController;
use App\Http\Controllers\BenchmarkingController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Public: Auth / Onboarding Views (no middleware) ──────────────────

Route::get('/', [AuthOnboardingController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthOnboardingController::class, 'showLogin']);
Route::get('/auth/login', [AuthOnboardingController::class, 'showLogin']);
Route::get('/auth/register/step-1', [AuthOnboardingController::class, 'showRegisterStep1']);
Route::get('/auth/register/step-2', [AuthOnboardingController::class, 'showRegisterStep2']);
Route::get('/auth/register/step-3', [AuthOnboardingController::class, 'showRegisterStep3']);

// ── Staff / Worker PIN Login (Branch + Worker ID) ────────────────────
Route::get('/staff/login', [\App\Http\Controllers\StaffAuthController::class, 'showLogin'])->name('staff.login');
Route::post('/staff/login', [\App\Http\Controllers\StaffAuthController::class, 'login'])->middleware('throttle:pin-login')->name('staff.login.post');

// ── Path A: Business Owner Onboarding ────────────────────────────────
Route::get('/auth/register/owner/step-2', [AuthOnboardingController::class, 'showOwnerStep2']);
Route::get('/auth/register/owner/step-3', [AuthOnboardingController::class, 'showOwnerStep3']);

// ── Path B: Branch Manager Onboarding ────────────────────────────────
Route::get('/auth/register/manager/step-2', [AuthOnboardingController::class, 'showManagerStep2']);
Route::get('/auth/register/manager/step-3', [AuthOnboardingController::class, 'showManagerStep3']);

// ── Session-based Login POST ─────────────────────────────────────────

Route::post('/login', function () {
    $credentials = request()->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, request()->boolean('remember'))) {
        request()->session()->regenerate();

        if (Auth::user()->isStaff()) {
            return redirect()->intended(route('staff.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    return back()
        ->withInput(request()->only('email'))
        ->withErrors(['email' => 'These credentials do not match our records.']);
})->middleware('throttle:login')->name('login.post');

// ── Authenticated Pages ──────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    // Staff have their own scoped /staff/dashboard (see below); this one shows
    // company-wide financials and every branch's alerts, so it's owner/manager
    // only -- login already redirects staff elsewhere, this closes the
    // direct-URL/sidebar-link gap that bypassed that.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')
        ->middleware('role:super_admin,manager');

    // ── Static Pages ──────────────────────────────────────────────────
    Route::get('/help-center', fn () => view('pages.help-center'))->name('help-center');
    Route::get('/help', fn () => view('help.index'))->name('help');
    Route::get('/about', fn () => view('pages.about'))->name('about');

    // ── Mail/Messages ─────────────────────────────────────────────────
    Route::get('/mail', [NoticesController::class, 'index'])->name('notices.index');
    Route::post('/mail', [NoticesController::class, 'store'])->name('notices.store');
    Route::delete('/mail/{notice}', [NoticesController::class, 'destroy'])->name('notices.destroy');

    /*
     * Everything below is closed to staff. These screens read across the whole
     * branch (pay rates, expense ledgers, revenue) and the controllers treat
     * "not a manager" as "may see every branch" — so without this gate a staff
     * account would see more than a manager, not less. Calendar/Meetings joined
     * this group too: the sidebar already hides it from staff, this just closes
     * the matching URL-guessing gap.
     */
    Route::middleware('role:super_admin,manager')->group(function () {
        // ── Calendar (main design — kept as-is, see PR notes) ────────────
        Route::get('/calendar', function () {
            $user = auth()->user();
            $isManager = $user->isManager();

            // Get branches for the meeting form
            $branches = \App\Models\Branch::when($isManager, fn ($q) => $q->where('id', $user->branch_id))
                ->orderBy('name')
                ->get();

            // Get meetings for the current month
            $meetings = \App\Models\Meeting::with(['branch', 'creator'])
                ->when($isManager && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            // Get meetings for the current week
            $weekMeetings = \App\Models\Meeting::with(['branch', 'creator'])
                ->when($isManager && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            // Get upcoming meetings
            $upcomingMeetings = \App\Models\Meeting::with(['branch', 'creator'])
                ->when($isManager && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('date', '>=', now()->toDateString())
                ->orderBy('date')
                ->orderBy('start_time')
                ->take(5)
                ->get();

            // Get days with events for calendar highlighting
            $eventDays = $meetings->pluck('date')->map(fn($d) => $d->day)->unique()->toArray();

            return view('calendar.index', [
                'branches' => $branches,
                'meetings' => $meetings,
                'weekMeetings' => $weekMeetings,
                'upcomingMeetings' => $upcomingMeetings,
                'eventDays' => $eventDays,
            ]);
        })->name('calendar');

        // ── Meeting AJAX Endpoints ────────────────────────────────────────
        Route::post('/calendar/meetings', function (\Illuminate\Http\Request $request) {
            $user = auth()->user();

            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'date' => ['required', 'date'],
                'start_time' => ['required', 'string', 'max:10'],
                'end_time' => ['required', 'string', 'max:10'],
                'meeting_type' => ['nullable', 'in:meeting,task,event'],
                'location' => ['nullable', 'string', 'max:255'],
                'branch_id' => ['nullable', 'exists:branches,id'],
            ]);

            if ($user->isManager()) {
                $validated['branch_id'] = $user->branch_id;
            }

            $validated['created_by'] = $user->id;
            $validated['status'] = 'scheduled';

            $meeting = \App\Models\Meeting::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Meeting created successfully.',
                'data' => $meeting->load(['branch', 'creator']),
            ], 201);
        })->name('calendar.meetings.store');

        Route::delete('/calendar/meetings/{meeting}', function (\App\Models\Meeting $meeting) {
            $user = auth()->user();
            if (! $user->isSuperAdmin() && ($meeting->branch_id === null || $user->branch_id !== $meeting->branch_id)) {
                abort(403, 'Forbidden: you do not have access to this branch.');
            }

            $meeting->delete();

            return response()->json([
                'status' => true,
                'message' => 'Meeting deleted successfully.',
            ]);
        })->name('calendar.meetings.delete');

        // ── Receipt OCR Scanning + Reconciliation ────────────────────────
        Route::get('/receipts', [\App\Http\Controllers\ReceiptsController::class, 'index'])->name('receipts.index');
        Route::post('/receipts', [\App\Http\Controllers\ReceiptsController::class, 'store'])->name('receipts.store');

        // ── Salary ────────────────────────────────────────────────────
        Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');
        Route::post('/salary/payslips', [SalaryController::class, 'generate'])->name('salary.generate');
        Route::get('/salary/payslips/{payslip}', [SalaryController::class, 'show'])->name('salary.show');
        Route::post('/salary/payslips/{payslip}/mark-paid', [SalaryController::class, 'markPaid'])->name('salary.mark-paid');

        // ── Payments ──────────────────────────────────────────────────
        Route::get('/payments', [PaymentsController::class, 'index'])->name('payments.index');
        Route::post('/payments', [PaymentsController::class, 'store'])->name('payments.store');
        Route::put('/payments/{payment}', [PaymentsController::class, 'update'])->name('payments.update');
        Route::post('/payments/{payment}/mark-paid', [PaymentsController::class, 'markPaid'])->name('payments.mark-paid');
        Route::delete('/payments/{payment}', [PaymentsController::class, 'destroy'])->name('payments.destroy');

        // ── Hiring ────────────────────────────────────────────────────
        Route::get('/hiring', [HiringController::class, 'index'])->name('hiring.index');
        Route::post('/hiring/openings', [HiringController::class, 'storeOpening'])->name('hiring.openings.store');
        Route::put('/hiring/openings/{opening}', [HiringController::class, 'updateOpening'])->name('hiring.openings.update');
        Route::delete('/hiring/openings/{opening}', [HiringController::class, 'destroyOpening'])->name('hiring.openings.destroy');
        Route::post('/hiring/openings/{opening}/applicants', [HiringController::class, 'storeApplicant'])->name('hiring.applicants.store');
        Route::put('/hiring/applicants/{applicant}/status', [HiringController::class, 'updateApplicantStatus'])->name('hiring.applicants.status');
        Route::delete('/hiring/applicants/{applicant}', [HiringController::class, 'destroyApplicant'])->name('hiring.applicants.destroy');

        // ── Legal Papers ──────────────────────────────────────────────
        Route::get('/legal-papers', [LegalPapersController::class, 'index'])->name('legal-papers.index');
        Route::get('/legal-papers/{document}/download', [LegalPapersController::class, 'download'])->name('legal-papers.download');
    });

    /*
     * Owner-only: setting what a worker is paid, and adding or removing the
     * company's legal records, are ownership decisions rather than day-to-day
     * branch management.
     */
    Route::middleware('role:super_admin')->group(function () {
        Route::put('/salary/workers/{user}/rate', [SalaryController::class, 'updateRate'])->name('salary.rate.update');

        Route::post('/legal-papers', [LegalPapersController::class, 'store'])->name('legal-papers.store');
        Route::put('/legal-papers/{document}', [LegalPapersController::class, 'update'])->name('legal-papers.update');
        Route::delete('/legal-papers/{document}', [LegalPapersController::class, 'destroy'])->name('legal-papers.destroy');
    });

    // ── Analytics + Reports (main design — kept as-is, see PR notes) ─────
    Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [\App\Http\Controllers\AnalyticsController::class, 'exportComparison'])->name('analytics.export');
    Route::get('/reports', function () {
        $user = auth()->user();
        $isManager = $user->isManager();
        $selectedBranchId = request()->query('branch_id') ? (int) request()->query('branch_id') : null;

        // Get branches for the business tabs
        $branches = \App\Models\Branch::when($isManager, fn ($q) => $q->where('id', $user->branch_id))
            ->orderBy('name')
            ->get();

        $branchScope = function ($query) use ($isManager, $user, $selectedBranchId) {
            if ($selectedBranchId) {
                $query->where('branch_id', $selectedBranchId);
            } elseif ($isManager && $user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        };

        // Get recent flags (last 7 days)
        $recentFlags = \App\Models\DiscrepancyAlert::with('branch', 'ingredient')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subDays(7))
            ->when(true, $branchScope)
            ->latest()
            ->get();

        // Get previous flags (older than 7 days or resolved)
        $previousFlags = \App\Models\DiscrepancyAlert::with('branch', 'ingredient')
            ->where(function ($q) use ($user, $isManager) {
                $q->where('status', '!=', 'pending')
                  ->orWhere('created_at', '<', now()->subDays(7));
            })
            ->when(true, $branchScope)
            ->latest()
            ->take(10)
            ->get();

        return view('reports.index', [
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'recentFlags' => $recentFlags,
            'previousFlags' => $previousFlags,
        ]);
    })->name('reports');

    Route::get('/reports/flags/{alert}/pdf', [\App\Http\Controllers\BranchDataController::class, 'flagPdf'])
        ->whereNumber('alert')
        ->name('reports.flags.pdf');

    // ── Staff Dashboard (self-service: clock in/out, verify stock) ─────
    Route::get('/staff/dashboard', [\App\Http\Controllers\StaffDashboardController::class, 'index'])
        ->name('staff.dashboard');
    Route::post('/staff/clock-in', [\App\Http\Controllers\StaffDashboardController::class, 'clockIn'])
        ->name('staff.clock-in');
    Route::post('/staff/clock-out', [\App\Http\Controllers\StaffDashboardController::class, 'clockOut'])
        ->name('staff.clock-out');
    Route::post('/staff/verify-stock', [\App\Http\Controllers\StaffDashboardController::class, 'verifyStock'])
        ->name('staff.verify-stock');
    Route::post('/staff/verify-till', [\App\Http\Controllers\StaffDashboardController::class, 'verifyTill'])
        ->name('staff.verify-till');
    Route::post('/staff/mark-prep-done', [\App\Http\Controllers\StaffDashboardController::class, 'markPrepDone'])
        ->name('staff.mark-prep-done');
    Route::post('/staff/mark-clean-done', [\App\Http\Controllers\StaffDashboardController::class, 'markCleanDone'])
        ->name('staff.mark-clean-done');
    Route::post('/staff/close-shift', [\App\Http\Controllers\StaffDashboardController::class, 'closeShift'])
        ->name('staff.close-shift');
    // Recipes expose cost/margin data and edit controls -- owner/manager
    // only, same reasoning as /dashboard: staff have no equivalent page to
    // redirect to here, so the sidebar link is hidden for them instead.
    Route::get('/branches', [BranchesController::class, 'index'])->name('branches');
    Route::post('/branches', [BranchesController::class, 'store'])->name('branches.store');
    Route::get('/branches/{branch}', [BranchesController::class, 'show'])->whereNumber('branch')->name('branches.show');
    Route::put('/branches/{branch}/description', [BranchesController::class, 'updateDescription'])->whereNumber('branch')->name('branches.description.update');
    Route::put('/branches/{branch}/disown', [BranchesController::class, 'disown'])->whereNumber('branch')->name('branches.disown');
    Route::get('/alerts', [AlertsController::class, 'index'])->name('alerts');
    Route::get('/map', [\App\Http\Controllers\MapController::class, 'index'])->name('map');

    // ── Notification inbox (bell dropdown) ───────────────────────────
    Route::get('/notifications', [\App\Http\Controllers\NotificationsController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/{notification}/read', [\App\Http\Controllers\NotificationsController::class, 'markRead'])->name('notifications.read');
    Route::put('/notifications/read-all', [\App\Http\Controllers\NotificationsController::class, 'markAllRead'])->name('notifications.read-all');



    // ── Recipe CRUD (AJAX endpoints — session auth) ────────────────────
    Route::middleware('role:super_admin,manager')->group(function () {
        Route::get('/business/recipes', [\App\Http\Controllers\BusinessRecipesController::class, '__invoke'])
            ->name('business.recipes');
        Route::get('/business/recipes/product/{product}/data', [\App\Http\Controllers\BusinessRecipesController::class, 'getProductData'])
            ->name('business.recipes.product.data');
        Route::get('/business/recipes/product/{product}/profile', [\App\Http\Controllers\BusinessRecipesController::class, 'ingredientProfile'])
            ->name('business.recipes.product.profile');
        Route::put('/business/recipes/product/{product}', [\App\Http\Controllers\BusinessRecipesController::class, 'updateProduct'])
            ->name('business.recipes.product.update');
        Route::post('/business/recipes/product/{product}/ingredient', [\App\Http\Controllers\BusinessRecipesController::class, 'addIngredient'])
            ->name('business.recipes.ingredient.add');
        Route::put('/business/recipes/ingredient/{recipe}', [\App\Http\Controllers\BusinessRecipesController::class, 'updateIngredient'])
            ->name('business.recipes.ingredient.update');
        Route::post('/business/recipes/ingredient/{recipe}/delete', [\App\Http\Controllers\BusinessRecipesController::class, 'removeIngredient'])
            ->name('business.recipes.ingredient.remove');
        Route::post('/business/recipes/product/{product}/delete', [\App\Http\Controllers\BusinessRecipesController::class, 'destroyProduct'])
            ->name('business.recipes.product.delete');
    });

    // ── Workers Directory (GET — owner/manager) ─────────────────────
    Route::middleware('role:super_admin,manager')->group(function () {
        Route::get('/business/workers', [\App\Http\Controllers\WorkersController::class, 'index'])
            ->name('business.workers');
    });

    // ── Workers CRUD (AJAX endpoints) ───────────────────────────────────
    Route::post('/business/workers', [\App\Http\Controllers\WorkersController::class, 'store'])
        ->name('business.workers.store');
    Route::put('/business/workers/{user}', [\App\Http\Controllers\WorkersController::class, 'update'])
        ->name('business.workers.update');
    Route::delete('/business/workers/{user}', [\App\Http\Controllers\WorkersController::class, 'destroy'])
        ->name('business.workers.destroy');

    // Alias for form-method spoofing (DELETE)
    Route::post('/business/workers/{user}/delete', [\App\Http\Controllers\WorkersController::class, 'destroy'])
        ->name('business.workers.delete');

    // ── Worker Profile CRUD ─────────────────────────────────────────────
    Route::put('/business/workers/{user}/profile', [\App\Http\Controllers\WorkersController::class, 'updateProfile'])
        ->name('business.workers.profile');

    // ── Attendance / Clock-In-Out ───────────────────────────────────────
    Route::post('/business/workers/{user}/clock-in', [\App\Http\Controllers\AttendanceController::class, 'clockIn'])
        ->name('business.workers.clock-in');
    Route::post('/business/workers/{user}/clock-out', [\App\Http\Controllers\AttendanceController::class, 'clockOut'])
        ->name('business.workers.clock-out');
    Route::get('/business/workers/{user}/attendance', [\App\Http\Controllers\AttendanceController::class, 'history'])
        ->name('business.workers.attendance');

    // ── Worker Activity (transactions, shifts, discrepancies) ─────────────
    Route::get('/business/workers/{user}/activity', \App\Http\Controllers\ActivityController::class)
        ->name('business.workers.activity');

    // ── Peer Reviews ────────────────────────────────────────────────────
    Route::post('/business/workers/{user}/peer-reviews', [\App\Http\Controllers\WorkersController::class, 'storePeerReview'])
        ->name('business.workers.peer-reviews.store');
    Route::delete('/business/workers/peer-reviews/{peerReview}', [\App\Http\Controllers\WorkersController::class, 'destroyPeerReview'])
        ->name('business.workers.peer-reviews.destroy');

    // ── Employee Goals ──────────────────────────────────────────────────
    Route::post('/business/workers/{user}/goals', [\App\Http\Controllers\WorkersController::class, 'storeGoal'])
        ->name('business.workers.goals.store');
    Route::put('/business/workers/goals/{goal}/status', [\App\Http\Controllers\WorkersController::class, 'updateGoalStatus'])
        ->name('business.workers.goals.status');
    Route::delete('/business/workers/goals/{goal}', [\App\Http\Controllers\WorkersController::class, 'destroyGoal'])
        ->name('business.workers.goals.destroy');

    // ── Logistics Page (Summary + Flags) ────────────────────────────
    Route::get('/logistics', function () {
        $user = auth()->user();
        $isManager = $user->isManager();

        // Stock summary with movements for estimated-amount calculation
        $stocks = \App\Models\BranchStock::with('ingredient', 'branch', 'movements')
            ->when($isManager, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->get();

        // Build per-item inventory rows
        $stockItems = $stocks->map(function ($stock) {
            $movements = $stock->movements;

            $initial  = $movements->where('type', \App\Models\StockMovement::TYPE_INITIAL)->sum('quantity_change');
            $restocks = $movements->where('type', \App\Models\StockMovement::TYPE_RESTOCK)->sum('quantity_change');
            $sales    = abs($movements->where('type', \App\Models\StockMovement::TYPE_SALE)->sum('quantity_change'));

            $estimated = ($initial + $restocks - $sales);
            // Fallback if no movements exist yet
            if ($estimated <= 0 && $stock->current_quantity > 0) {
                $estimated = $stock->current_quantity;
            }

            return (object) [
                'id'               => $stock->id,
                'item_name'        => $stock->ingredient?->name ?? 'Unknown Ingredient',
                'unit'             => $stock->ingredient?->unit ?? '',
                'branch_id'        => $stock->branch_id,
                'branch_name'      => $stock->branch?->name ?? 'Unknown',
                'estimated_amount' => max(0, $estimated),
                'on_site_amount'   => $stock->current_quantity,
                'min_threshold'    => $stock->min_threshold,
                'status'           => $stock->stock_status,
            ];
        })->sortBy('branch_name')->values();

        // Recent stock movements (last 20)
        $recentMovements = \App\Models\StockMovement::with('branchStock.ingredient', 'branchStock.branch', 'user')
            ->when($isManager, fn ($q) => $q->whereHas('branchStock', fn ($q) => $q->where('branch_id', $user->branch_id)))
            ->latest()
            ->take(20)
            ->get();

        // Active discrepancy alerts
        $activeAlerts = \App\Models\DiscrepancyAlert::with('branch', 'ingredient')
            ->where('status', 'open')
            ->when($isManager, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->latest()
            ->get();

        // Recent transactions (last 10)
        $recentTransactions = \App\Models\Transaction::with('product', 'branch')
            ->when($isManager, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->latest()
            ->take(10)
            ->get();

        // Branches (for filtering)
        $branches = \App\Models\Branch::when($isManager, fn ($q) => $q->where('id', $user->branch_id))->orderBy('name')->get();

        $tab = in_array(request()->query('tab'), ['summary', 'flags'], true)
            ? request()->query('tab')
            : 'summary';

        $totalStockItems = $stockItems->count();

        return view('logistics.index', [
            'tab'                => $tab,
            'branches'           => $branches,
            'stockItems'         => $stockItems,
            'stocks'             => $stocks,
            'totalStockItems'    => $totalStockItems,
            'recentMovements'    => $recentMovements,
            'activeAlerts'       => $activeAlerts,
            'recentTransactions' => $recentTransactions,
        ]);
    })->name('logistics');



    // ── API Documentation (protected — requires auth) ─────────────────
    Route::get('/api-docs', function () {
        $path = public_path('_api-docs.html');
        if (! file_exists($path)) {
            abort(404, 'API documentation not found.');
        }
        return response()->file($path);
    })->name('api.docs');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/payment-categories', [SettingsController::class, 'addPaymentCategory'])->name('settings.payment-categories.store');
    Route::delete('/settings/payment-categories/{category}', [SettingsController::class, 'removePaymentCategory'])->name('settings.payment-categories.destroy');

    // ── Profit & Loss ──────────────────────────────────────────────
    Route::get('/profit-loss', [ProfitLossController::class, 'index'])->name('profit-loss');

    // ── Inventory Intelligence ──────────────────────────────────────
    Route::get('/inventory', [InventoryIntelligenceController::class, 'index'])->name('inventory.intelligence');

    // ── Purchase Orders ─────────────────────────────────────────────
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::patch('/purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.update-status');
    Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');

    // ── Leave Requests ─────────────────────────────────────────────
    Route::get('/leave', [LeaveRequestController::class, 'index'])->name('leave.index');
    Route::post('/leave', [LeaveRequestController::class, 'store'])->name('leave.store');
    Route::patch('/leave/{leaveRequest}/status', [LeaveRequestController::class, 'updateStatus'])->name('leave.update-status');
    Route::delete('/leave/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('leave.destroy');

    // ── Audit Trail ────────────────────────────────────────────────
    Route::get('/audit', [AuditTrailController::class, 'index'])->name('audit.index');
    Route::get('/audit/{auditLog}', [AuditTrailController::class, 'show'])->name('audit.show');

    // ── Invoices ───────────────────────────────────────────────────
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

    // ── Forecasting ────────────────────────────────────────────────
    Route::get('/forecasting', [ForecastingController::class, 'index'])->name('forecasting');

    // ── Customers ──────────────────────────────────────────────────
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/feedback', [CustomerController::class, 'storeFeedback'])->name('customers.feedback.store');
    Route::post('/customers/{customer}/points', [CustomerController::class, 'addPoints'])->name('customers.add-points');

    // ── Equipment Maintenance ──────────────────────────────────────
    Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
    Route::post('/equipment', [EquipmentController::class, 'store'])->name('equipment.store');
    Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
    Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy');

    // ── Custom Reports ─────────────────────────────────────────────
    Route::get('/reports/custom', [CustomReportController::class, 'index'])->name('reports.custom');
    Route::post('/reports/custom/generate', [CustomReportController::class, 'generate'])->name('reports.custom.generate');

    // ── Health & Safety Checklists ─────────────────────────────────
    Route::get('/safety', [SafetyChecklistController::class, 'index'])->name('safety.index');
    Route::get('/safety/create', [SafetyChecklistController::class, 'create'])->name('safety.create');
    Route::post('/safety', [SafetyChecklistController::class, 'store'])->name('safety.store');
    Route::get('/safety/{checklist}', [SafetyChecklistController::class, 'show'])->name('safety.show');

    // ── Benchmarking ───────────────────────────────────────────────
    Route::get('/benchmarking', [BenchmarkingController::class, 'index'])->name('benchmarking');

    // ── Pricing Simulator ─────────────────────────────────────────────
    Route::get('/pricing', [\App\Http\Controllers\PricingController::class, 'index'])
        ->name('pricing.index');
    Route::get('/pricing/simulate', [\App\Http\Controllers\PricingController::class, 'simulate'])
        ->name('pricing.simulate');
    Route::middleware('role:super_admin,manager')->group(function () {
        Route::get('/pricing/ingredients', [\App\Http\Controllers\IngredientPricingController::class, 'index'])
            ->name('pricing.ingredients');
        Route::get('/pricing/ingredients/{ingredient}/data', [\App\Http\Controllers\IngredientPricingController::class, 'getPricingData'])
            ->name('pricing.ingredients.data');
        Route::post('/pricing/ingredients/{ingredient}/pricing', [\App\Http\Controllers\IngredientPricingController::class, 'updatePricing'])
            ->name('pricing.ingredients.update');
    });

    // ── Supplier Directory (owner/manager only — pricing data) ─────────
    Route::middleware('role:super_admin,manager')->group(function () {
        Route::get('/suppliers', [\App\Http\Controllers\SupplierController::class, 'index'])
            ->name('suppliers.index');
        Route::post('/suppliers', [\App\Http\Controllers\SupplierController::class, 'store'])
            ->name('suppliers.store');
        Route::get('/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'show'])
            ->name('suppliers.show');
        Route::put('/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'update'])
            ->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'destroy'])
            ->name('suppliers.destroy');
        Route::post('/suppliers/{supplier}/ingredients', [\App\Http\Controllers\SupplierController::class, 'linkIngredient'])
            ->name('suppliers.link-ingredient');
        Route::delete('/suppliers/{supplier}/ingredients/{ingredient}', [\App\Http\Controllers\SupplierController::class, 'unlinkIngredient'])
            ->name('suppliers.unlink-ingredient');
        Route::post('/suppliers/{supplier}/purchases', [\App\Http\Controllers\SupplierController::class, 'addPurchase'])
            ->name('suppliers.add-purchase');
        Route::get('/suppliers/import', [\App\Http\Controllers\SupplierImportController::class, 'index'])
            ->name('suppliers.import');
        Route::post('/suppliers/import/preview', [\App\Http\Controllers\SupplierImportController::class, 'preview'])
            ->name('suppliers.import.preview');
        Route::post('/suppliers/import/execute', [\App\Http\Controllers\SupplierImportController::class, 'execute'])
            ->name('suppliers.import.execute');

        // ── Geocoding API (owner/manager — modifies branch/supplier data)
        Route::post('/api/geocode/address', [\App\Http\Controllers\GeocodingController::class, 'geocodeAddress'])
            ->name('api.geocode.address');
        Route::post('/api/geocode/branches', [\App\Http\Controllers\GeocodingController::class, 'geocodeBranches'])
            ->name('api.geocode.branches');
        Route::post('/api/geocode/suppliers', [\App\Http\Controllers\GeocodingController::class, 'geocodeSuppliers'])
            ->name('api.geocode.suppliers');
    });

    // ── AJAX Branch Data Endpoints (main design — kept as-is) ───────────
    Route::get('/ajax/analytics', [\App\Http\Controllers\AnalyticsController::class, 'data'])->name('ajax.analytics');
    Route::get('/ajax/reports', [\App\Http\Controllers\BranchDataController::class, 'reports'])->name('ajax.reports');
    Route::get('/ajax/workers', [\App\Http\Controllers\BranchDataController::class, 'workers'])->name('ajax.workers');
    Route::get('/ajax/summary', [\App\Http\Controllers\BranchDataController::class, 'summary'])->name('ajax.summary');
    Route::get('/ajax/logistics', [\App\Http\Controllers\BranchDataController::class, 'logistics'])->name('ajax.logistics');
    Route::get('/ajax/branches', [\App\Http\Controllers\BranchDataController::class, 'branches'])->name('ajax.branches');

    // ── Ingredients (main design — kept as-is) ──────────────────────────
    Route::get('/ingredients', [\App\Http\Controllers\IngredientController::class, 'index'])->name('ingredients');
    Route::post('/ingredients', [\App\Http\Controllers\IngredientController::class, 'store'])->name('ingredients.store');
    Route::put('/ingredients/{ingredient}', [\App\Http\Controllers\IngredientController::class, 'update'])->name('ingredients.update');
    Route::delete('/ingredients/{ingredient}', [\App\Http\Controllers\IngredientController::class, 'destroy'])->name('ingredients.destroy');
});

// ── Logout ───────────────────────────────────────────────────────────

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
