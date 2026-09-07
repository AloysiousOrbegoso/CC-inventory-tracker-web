<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();
        $status = $request->query('status');
        $branchId = $request->query('branch_id');

        $query = Invoice::with(['branch', 'creator']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($isManager) {
            $query->where('branch_id', $user->branch_id);
        }

        $invoices = $query->latest()->paginate(20);

        // Stats
        $statsQuery = Invoice::query();
        if ($isManager) {
            $statsQuery->where('branch_id', $user->branch_id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
            'sent' => (clone $statsQuery)->where('status', 'sent')->count(),
            'paid' => (clone $statsQuery)->where('status', 'paid')->count(),
            'overdue' => (clone $statsQuery)->where('status', 'overdue')->count(),
            'totalRevenue' => (clone $statsQuery)->where('status', 'paid')->sum('total'),
        ];

        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('invoices.index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'status' => $status,
            'branchId' => $branchId,
            'branches' => $branches,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers create invoices.']);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'discount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if ($user->isManager() && $user->branch_id !== (int) $validated['branch_id']) {
            return back()->withErrors(['branch_id' => 'You can only create invoices for your own branch.']);
        }

        $invoice = DB::transaction(function () use ($validated, $user) {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'branch_id' => $validated['branch_id'],
                'created_by' => $user->id,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_address' => $validated['customer_address'] ?? null,
                'tax_rate' => $validated['tax_rate'],
                'discount' => $validated['discount'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            $invoice->recalculateTotals();
            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice ' . $invoice->invoice_number . ' created successfully.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['branch', 'creator', 'items']);

        return view('invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers update invoices.']);
        }

        $validated = $request->validate([
            'status' => 'required|in:sent,paid,overdue,cancelled',
        ]);

        if ($user->isManager() && $user->branch_id !== $invoice->branch_id) {
            return back()->withErrors(['status' => 'Forbidden.']);
        }

        $data = ['status' => $validated['status']];
        if ($validated['status'] === 'paid') {
            $data['paid_at'] = now();
        }

        $invoice->update($data);

        return back()->with('success', 'Invoice status updated to ' . ucfirst($validated['status']) . '.');
    }

    public function destroy(Invoice $invoice)
    {
        $user = request()->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers delete invoices.']);
        }

        if ($user->isManager() && $user->branch_id !== $invoice->branch_id) {
            return back()->withErrors(['error' => 'Forbidden.']);
        }

        if (! in_array($invoice->status, ['draft'])) {
            return back()->withErrors(['error' => 'Only draft invoices can be deleted.']);
        }

        $invoiceNumber = $invoice->invoice_number;
        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice ' . $invoiceNumber . ' deleted.');
    }
}
