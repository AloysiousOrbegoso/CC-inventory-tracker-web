@php
    $statusBadge = ['pending' => 'badge-amber', 'paid' => 'badge-green', 'overdue' => 'badge-red'];
@endphp
@extends('layouts.sidebar')

@section('title', 'Payments')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="text-[22px] font-extrabold tracking-tight">Payments</div>
        <div class="text-[13px] text-ink-2 mt-0.5">Rent, utilities, supplier invoices, and other outgoing expenses</div>
    </div>
    @if(auth()->user()->isManager())
    <button type="button" class="btn-primary" onclick="openPaymentModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Record Payment
    </button>
    @endif
</div>

<div class="grid grid-cols-[repeat(auto-fit,minmax(160px,1fr))] gap-3 mb-6">
    <div class="card p-5">
        <div class="text-[26px] font-extrabold text-amber-600">&#8369;{{ number_format($totals['pending'], 2) }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Pending</div>
    </div>
    <div class="card p-5">
        <div class="text-[26px] font-extrabold text-green-600">&#8369;{{ number_format($totals['paid'], 2) }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Paid</div>
    </div>
    <div class="card p-5">
        <div class="text-[26px] font-extrabold text-red-600">&#8369;{{ number_format($totals['overdue'], 2) }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Overdue</div>
    </div>
</div>

@if ($payments->isNotEmpty())
    @include('partials._filter-toolbar', [
        'ft_id' => 'paymentsFilter',
        'ft_target' => '#paymentsTbody tr',
        'ft_searchPlaceholder' => 'Search payee…',
        'ft_branches' => $branches,
        'ft_date' => true,
        'ft_statusOptions' => ['pending' => 'Pending', 'paid' => 'Paid', 'overdue' => 'Overdue'],
    ])
@endif

<div class="summary-table-wrap">
    @if ($payments->isEmpty())
        <div class="empty-state-icon" style="padding:32px 20px">
            <div style="font-size:32px;margin-bottom:8px">💳</div>
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No payments yet</div>
            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">Track rent, utilities, supplier invoices, and other expenses.</div>
            <div style="padding:10px 14px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:260px;margin:0 auto">
                <div style="font-weight:600;color:var(--text);margin-bottom:4px">💰 Expense categories:</div>
                <div style="margin-bottom:3px">• Rent & Utilities</div>
                <div style="margin-bottom:3px">• Supplier Invoices</div>
                <div style="margin-bottom:3px">• Equipment Maintenance</div>
                <div>• Marketing & Miscellaneous</div>
            </div>
            <button class="btn-primary" style="margin-top:14px" onclick="openPaymentModal()">+ Record First Payment</button>
        </div>
    @else
        <table class="summary-table">
            <thead><tr><th>Payee</th><th>Category</th><th>Branch</th><th>Method</th><th>Amount</th><th>Due</th><th>Status</th><th></th></tr></thead>
            <tbody id="paymentsTbody">
                @foreach ($payments as $payment)
                <tr data-search="{{ strtolower($payment->payee) }}" data-branch-id="{{ $payment->branch_id }}" data-status="{{ $payment->status }}" data-created="{{ $payment->created_at->timestamp }}">
                    <td>
                        <div class="font-bold">{{ $payment->payee }}</div>
                        @if ($payment->receipt_photo)
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($payment->receipt_photo) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-[11px] text-accent no-underline hover:underline mt-0.5">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Receipt
                            </a>
                        @endif
                    </td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment->category)) }}</td>
                    <td>{{ $payment->branch?->name ?? '—' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                    <td class="font-bold text-accent">&#8369;{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->due_date?->format('M d, Y') ?? '—' }}</td>
                    <td><span class="{{ $statusBadge[$payment->status] ?? 'badge-gray' }}">{{ ucfirst($payment->status) }}</span></td>
                    <td>
                        <div class="flex gap-1.5">
                            @if ($payment->status !== 'paid')
                                <button class="btn-sm" onclick="markPaid({{ $payment->id }})">Mark Paid</button>
                            @endif
                            @if(auth()->user()->isManager())
                            <button class="btn-sm" onclick='openEditModal(@json($payment))'>Edit</button>
                            <button class="btn-sm danger" onclick="deletePayment({{ $payment->id }}, '{{ addslashes($payment->payee) }}')">Del</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- RECORD/EDIT PAYMENT MODAL --}}
<div class="modal-overlay" id="paymentModal">
    <div class="modal-box">
        <h2 class="text-lg font-extrabold mb-5" id="paymentModalTitle">Record Payment</h2>
        <form id="paymentForm" onsubmit="savePayment(event)">
            <input type="hidden" id="paymentId">
            <div class="form-group"><div class="form-label">Payee *</div><input type="text" class="form-input" id="pPayee" required placeholder="e.g. Meralco, Landlord, ABC Supplies"></div>
            <div class="flex gap-3">
                <div class="form-group flex-1">
                    <div class="form-label">Category</div>
                    <select class="form-input" id="pCategory">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}">{{ ucwords(str_replace('_', ' ', $cat)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group flex-1">
                    <div class="form-label">Method</div>
                    <select class="form-input" id="pMethod">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="gcash">GCash</option>
                        <option value="check">Check</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            @if ($branches->count() > 1)
            <div class="form-group">
                <div class="form-label">Branch *</div>
                <select class="form-input" id="pBranch" required>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" id="pBranch" value="{{ $branches->first()->id ?? '' }}">
            @endif
            <div class="flex gap-3">
                <div class="form-group flex-1"><div class="form-label">Amount *</div><input type="number" step="0.01" class="form-input" id="pAmount" required placeholder="0.00"></div>
                <div class="form-group flex-1"><div class="form-label">Due Date</div><input type="date" class="form-input" id="pDueDate"></div>
            </div>
            <div class="form-group">
                <div class="form-label">Status</div>
                <select class="form-input" id="pStatus">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
            <div class="form-group" id="pReceiptGroup"><div class="form-label">Receipt <span class="opacity-50 font-normal">(image, max 5MB)</span></div><input type="file" class="form-input" id="pReceipt" accept="image/*"></div>
            <div class="form-group"><div class="form-label">Notes</div><textarea class="form-input" id="pNotes"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save" id="paymentSubmitBtn">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function closeModal() { document.getElementById('paymentModal').classList.remove('is-open'); }

function openPaymentModal() {
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentId').value = '';
    document.getElementById('paymentModalTitle').textContent = 'Record Payment';
    document.getElementById('pReceiptGroup').style.display = '';
    document.getElementById('paymentModal').classList.add('is-open');
}

function openEditModal(payment) {
    document.getElementById('paymentId').value = payment.id;
    document.getElementById('pPayee').value = payment.payee;
    document.getElementById('pCategory').value = payment.category;
    document.getElementById('pMethod').value = payment.method;
    if (document.getElementById('pBranch').tagName === 'SELECT') document.getElementById('pBranch').value = payment.branch_id;
    document.getElementById('pAmount').value = payment.amount;
    document.getElementById('pDueDate').value = payment.due_date ? payment.due_date.slice(0, 10) : '';
    document.getElementById('pStatus').value = payment.status;
    document.getElementById('pNotes').value = payment.notes ?? '';
    document.getElementById('paymentModalTitle').textContent = 'Edit Payment';
    document.getElementById('pReceiptGroup').style.display = 'none';
    document.getElementById('paymentModal').classList.add('is-open');
}

async function savePayment(e) {
    e.preventDefault();
    const id = document.getElementById('paymentId').value;
    const btn = document.getElementById('paymentSubmitBtn');
    btn.disabled = true; btn.textContent = 'Saving…';

    if (!id) {
        const fd = new FormData();
        fd.append('payee', document.getElementById('pPayee').value);
        fd.append('category', document.getElementById('pCategory').value);
        fd.append('method', document.getElementById('pMethod').value);
        fd.append('branch_id', document.getElementById('pBranch').value || '');
        fd.append('amount', document.getElementById('pAmount').value);
        fd.append('due_date', document.getElementById('pDueDate').value);
        fd.append('status', document.getElementById('pStatus').value);
        fd.append('notes', document.getElementById('pNotes').value);
        const file = document.getElementById('pReceipt').files[0];
        if (file) fd.append('receipt', file);
        const res = await fetch('{{ route('payments.store') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd });
        if (res.ok) location.reload(); else { alert('Error saving payment.'); btn.disabled = false; btn.textContent = 'Save Payment'; }
    } else {
        const body = {
            payee: document.getElementById('pPayee').value,
            category: document.getElementById('pCategory').value,
            method: document.getElementById('pMethod').value,
            amount: document.getElementById('pAmount').value,
            due_date: document.getElementById('pDueDate').value || null,
            status: document.getElementById('pStatus').value,
            notes: document.getElementById('pNotes').value || null,
        };
        const res = await fetch(`{{ url('/payments') }}/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify(body) });
        if (res.ok) location.reload(); else { alert('Error saving payment.'); btn.disabled = false; btn.textContent = 'Save Payment'; }
    }
}

async function markPaid(id) {
    const res = await fetch(`{{ url('/payments') }}/${id}/mark-paid`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
    if (res.ok) location.reload(); else alert('Error marking as paid.');
}

async function deletePayment(id, payee) {
    if (!confirm(`Delete payment to "${payee}"?`)) return;
    const res = await fetch(`{{ url('/payments') }}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
    if (res.ok) location.reload();
}

document.querySelectorAll('.modal-overlay').forEach(el => el.addEventListener('click', e => { if (e.target === el) el.classList.remove('is-open'); }));
</script>
@endsection
