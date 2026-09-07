@php
    $statusBadge = ['pending' => 'badge-amber', 'matched' => 'badge-green', 'mismatched' => 'badge-red', 'unreadable' => 'badge-gray'];
@endphp
@extends('layouts.sidebar')

@section('title', 'Receipts')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="text-[22px] font-extrabold tracking-tight">Receipts</div>
        <div class="text-[13px] text-ink-2 mt-0.5">Scan a receipt photo — OCR reads the total and matches it against POS transactions</div>
    </div>
    @if(auth()->user()->isManager())
    <button type="button" class="btn-primary" onclick="openScanModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Scan Receipt
    </button>
    @endif
</div>

<div class="grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-3 mb-6">
    <div class="card p-5">
        <div class="text-[26px] font-extrabold">{{ $summary['total'] }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Total Scanned</div>
    </div>
    <div class="card p-5">
        <div class="text-[26px] font-extrabold text-green-600">{{ $summary['matched'] }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Matched</div>
    </div>
    <div class="card p-5">
        <div class="text-[26px] font-extrabold text-red-600">{{ $summary['mismatched'] }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Mismatched</div>
    </div>
    <div class="card p-5">
        <div class="text-[26px] font-extrabold text-amber-600">{{ $summary['pending'] }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Pending</div>
    </div>
    <div class="card p-5">
        <div class="text-[26px] font-extrabold text-ink-3">{{ $summary['unreadable'] }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Unreadable</div>
    </div>
</div>

<div class="summary-table-wrap">
    @if ($receipts->isEmpty())
        <div class="empty-state-icon" style="padding:32px 20px">
            <div style="font-size:32px;margin-bottom:8px">🧾</div>
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No receipts scanned yet</div>
            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">Scan receipt photos and OCR will match them against POS transactions.</div>
            <div style="padding:10px 14px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:280px;margin:0 auto">
                <div style="font-weight:600;color:var(--text);margin-bottom:4px">📸 How it works:</div>
                <div style="margin-bottom:3px">• Upload a photo of a receipt</div>
                <div style="margin-bottom:3px">• OCR reads the total amount</div>
                <div style="margin-bottom:3px">• System matches it to a transaction</div>
                <div>• Discrepancies are flagged for review</div>
            </div>
            <button class="btn-primary" style="margin-top:14px" onclick="openScanModal()">+ Scan First Receipt</button>
        </div>
    @else
        <table class="summary-table">
            <thead><tr><th>Receipt</th><th>Branch</th><th>OCR Amount</th><th>Matched Transaction</th><th>Status</th><th>Scanned</th></tr></thead>
            <tbody>
                @foreach ($receipts as $receipt)
                <tr>
                    <td>
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($receipt->image_path) }}" target="_blank"
                           class="inline-flex items-center gap-1 text-[12px] text-accent no-underline hover:underline">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            View image
                        </a>
                    </td>
                    <td>{{ $receipt->branch?->name ?? '—' }}</td>
                    <td class="font-bold">{{ $receipt->parsed_total_amount !== null ? '₱'.number_format($receipt->parsed_total_amount, 2) : '—' }}</td>
                    <td>{{ $receipt->matchedTransaction ? '#'.$receipt->matchedTransaction->id.' — '.($receipt->matchedTransaction->product?->name ?? '') : '—' }}</td>
                    <td><span class="{{ $statusBadge[$receipt->reconciliation_status] ?? 'badge-gray' }}">{{ ucfirst($receipt->reconciliation_status) }}</span></td>
                    <td>{{ $receipt->scanned_at?->format('M d, Y g:i A') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- SCAN RECEIPT MODAL --}}
<div class="modal-overlay" id="scanModal">
    <div class="modal-box">
        <h2 class="text-lg font-extrabold mb-5">Scan Receipt</h2>
        <form id="scanForm" onsubmit="scanReceipt(event)">
            @if ($branches->count() > 1)
            <div class="form-group">
                <div class="form-label">Branch *</div>
                <select class="form-input" id="rBranch" required>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" id="rBranch" value="{{ $branches->first()->id ?? '' }}">
            @endif
            <div class="form-group">
                <div class="form-label">Receipt Photo * <span class="opacity-50 font-normal">(image, max 5MB)</span></div>
                <input type="file" class="form-input" id="rImage" accept="image/*" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save" id="scanSubmitBtn">Scan</button>
            </div>
        </form>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function closeModal() { document.getElementById('scanModal').classList.remove('is-open'); }

function openScanModal() {
    document.getElementById('scanForm').reset();
    document.getElementById('scanModal').classList.add('is-open');
}

async function scanReceipt(e) {
    e.preventDefault();
    const btn = document.getElementById('scanSubmitBtn');
    btn.disabled = true; btn.textContent = 'Scanning…';

    const fd = new FormData();
    fd.append('branch_id', document.getElementById('rBranch').value || '');
    const file = document.getElementById('rImage').files[0];
    if (file) fd.append('image', file);

    const res = await fetch('{{ route('receipts.store') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd });
    if (res.ok) {
        location.reload();
    } else {
        const data = await res.json().catch(() => ({}));
        alert(data.message || 'Error scanning receipt.');
        btn.disabled = false; btn.textContent = 'Scan';
    }
}

document.querySelectorAll('.modal-overlay').forEach(el => el.addEventListener('click', e => { if (e.target === el) el.classList.remove('is-open'); }));
</script>
@endsection
