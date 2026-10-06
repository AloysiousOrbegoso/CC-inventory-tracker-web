<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentsController extends Controller
{

    /** Seed value for the owner-editable list kept in AppSetting('payment_categories'). */
    public const DEFAULT_CATEGORIES = [
        'rent', 'utilities', 'supplier', 'salary', 'maintenance',
        'packaging', 'utensils', 'gas', 'wages', 'other',
    ];

    /**
     * Owners manage this list from Settings; it is no longer a fixed enum.
     * 'other' is always present — it's the column default and the fallback
     * every payment falls back to if its category is ever removed.
     */
    public static function categories(): array
    {
        $stored = json_decode(AppSetting::get('payment_categories', ''), true);
        $categories = is_array($stored) && $stored !== [] ? $stored : self::DEFAULT_CATEGORIES;

        return in_array('other', $categories, true) ? $categories : [...$categories, 'other'];
    }

    public function index(): View
    {
        $user = auth()->user();
        $isManager = $user->isManager();
        $branchId = $isManager ? $user->branch_id : null;

        $payments = Payment::with('branch', 'recorder')
            ->when($isManager, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        $branches = Branch::when($isManager, fn ($q) => $q->where('id', $branchId))->orderBy('name')->get();

        $totals = [
            'pending' => (float) $payments->where('status', 'pending')->sum('amount'),
            'paid' => (float) $payments->where('status', 'paid')->sum('amount'),
            'overdue' => (float) $payments->where('status', 'overdue')->sum('amount'),
        ];

        return view('payments.index', [
            'payments' => $payments,
            'branches' => $branches,
            'totals' => $totals,
            'categories' => self::categories(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Owner is an overseer — only managers record payments
        if (! $user->isManager()) {
            return response()->json(['message' => 'Forbidden. Managers record payments.'], 403);
        }

        $isManager = true;

        $validated = $request->validate([
            'branch_id' => [$isManager ? 'nullable' : 'required', 'exists:branches,id'],
            'category' => ['required', 'in:'.implode(',', self::categories())],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'in:cash,bank_transfer,gcash,check,other'],
            'status' => ['required', 'in:pending,paid,overdue'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'receipt' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('receipt')) {
            $validated['receipt_photo'] = $request->file('receipt')->store('payments', 'public');
        }
        unset($validated['receipt']);

        $validated['branch_id'] = $isManager ? $user->branch_id : $validated['branch_id'];
        $validated['recorded_by'] = $user->id;
        $validated['paid_at'] = $validated['status'] === 'paid' ? now() : null;

        $payment = Payment::create($validated);

        return response()->json(['success' => true, 'payment' => $payment->load('branch', 'recorder')], 201);
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('manage', $payment);

        $validated = $request->validate([
            'category' => ['required', 'in:'.implode(',', self::categories())],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'in:cash,bank_transfer,gcash,check,other'],
            'status' => ['required', 'in:pending,paid,overdue'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'paid' && $payment->status !== 'paid') {
            $validated['paid_at'] = now();
        } elseif ($validated['status'] !== 'paid') {
            $validated['paid_at'] = null;
        }

        $payment->update($validated);

        return response()->json(['success' => true, 'payment' => $payment->load('branch', 'recorder')]);
    }

    public function markPaid(Payment $payment): JsonResponse
    {
        $this->authorize('manage', $payment);

        $payment->update(['status' => 'paid', 'paid_at' => now()]);

        return response()->json(['success' => true, 'payment' => $payment->load('branch', 'recorder')]);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('manage', $payment);

        $payment->delete();

        return response()->json(['success' => true]);
    }
}
