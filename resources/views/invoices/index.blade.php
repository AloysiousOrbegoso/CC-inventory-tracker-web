@extends('layouts.sidebar')

@section('title', 'Invoices')

@section('styles')
    .inv-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    .inv-stat {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .inv-stat__value {
        font-size: 24px;
        font-weight: 800;
        color: var(--text);
    }
    .inv-stat__label {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-3);
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-top: 4px;
    }

    .inv-filters {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        align-items: center;
    }
    .inv-filter-btn {
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        cursor: pointer;
        transition: all .15s;
    }
    .inv-filter-btn:hover { border-color: var(--accent); }
    .inv-filter-btn.active {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }
    .inv-filter-select {
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        font-family: var(--font);
    }

    .inv-create-btn {
        margin-left: auto;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        background: var(--accent);
        color: #fff;
        cursor: pointer;
        transition: all .15s;
    }
    .inv-create-btn:hover { opacity: .9; }

    .status-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pill.draft { background: #f3f4f6; color: #374151; }
    .status-pill.sent { background: #dbeafe; color: #1d4ed8; }
    .status-pill.paid { background: #dcfce7; color: #15803d; }
    .status-pill.overdue { background: #fee2e2; color: #dc2626; }
    .status-pill.cancelled { background: #f3f4f6; color: #6b7280; }

    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-content {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        width: 90%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 20px;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group.full { grid-column: span 2; }
    .form-label { font-size: 12px; font-weight: 600; color: var(--accent); }
    .form-input {
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 13px;
        font-family: var(--font);
    }
    .form-input:focus { outline: none; border-color: var(--accent); }

    .inv-items-header {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 40px;
        gap: 10px;
        margin-bottom: 8px;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-3);
        text-transform: uppercase;
    }
    .inv-item-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 40px;
        gap: 10px;
        margin-bottom: 8px;
        align-items: center;
    }
    .inv-item-row .form-input { padding: 8px 10px; font-size: 12px; }
    .btn-remove-item {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: none;
        background: #fee2e2;
        color: #dc2626;
        cursor: pointer;
        font-size: 14px;
    }
    .btn-add-item {
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1px dashed var(--border);
        background: transparent;
        color: var(--accent);
        cursor: pointer;
        margin-top: 8px;
    }
    .btn-submit {
        margin-top: 20px;
        padding: 12px 28px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        background: #16a34a;
        color: #fff;
        cursor: pointer;
    }
    .btn-submit:hover { background: #15803d; }

    .flash-success {
        background: #dcfce7;
        border: 1px solid #16a34a;
        color: #166534;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 13px;
    }
    .flash-error {
        background: #fee2e2;
        border: 1px solid #dc2626;
        color: #991b1b;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 13px;
    }
@endsection

@section('content')
@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="flash-error">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<!-- Stats -->
<div class="inv-summary">
    <div class="inv-stat">
        <div class="inv-stat__value">{{ $stats['total'] }}</div>
        <div class="inv-stat__label">Total Invoices</div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat__value" style="color:#374151">{{ $stats['draft'] }}</div>
        <div class="inv-stat__label">Draft</div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat__value" style="color:#1d4ed8">{{ $stats['sent'] }}</div>
        <div class="inv-stat__label">Sent</div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat__value" style="color:#15803d">₱{{ number_format($stats['totalRevenue'], 0) }}</div>
        <div class="inv-stat__label">Paid Revenue</div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat__value" style="color:#dc2626">{{ $stats['overdue'] }}</div>
        <div class="inv-stat__label">Overdue</div>
    </div>
</div>

<!-- Filters -->
<div class="inv-filters">
    @foreach(['all' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue'] as $key => $label)
        <button class="inv-filter-btn {{ ($status ?? 'all') === $key ? 'active' : '' }}"
                onclick="setStatus('{{ $key }}')">{{ $label }}</button>
    @endforeach

    @if($branches->count() > 1)
        <select class="inv-filter-select" onchange="setBranch(this.value)">
            <option value="">All Branches</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    @endif

    @if(auth()->user()->isManager())
    <button class="inv-create-btn" onclick="openCreateModal()">+ New Invoice</button>
    @endif
</div>

<!-- Invoices List -->
<div class="inv-panel">
    @if($invoices->isEmpty())
        <div class="empty-state" style="padding:32px">
            <div style="font-size:28px;margin-bottom:8px">🧾</div>
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No invoices yet</div>
            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">Create invoices for customers and track payment status.</div>
            <div style="padding:10px 14px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:240px;margin:0 auto">
                <div style="font-weight:600;color:var(--text);margin-bottom:4px">📋 Invoice features:</div>
                <div style="margin-bottom:3px">• Generate from transactions</div>
                <div style="margin-bottom:3px">• Track pending/paid status</div>
                <div style="margin-bottom:3px">• Auto-calculate totals</div>
                <div>• Export for accounting</div>
            </div>
            <button class="inv-create-btn" style="margin-top:14px" onclick="openCreateModal()">+ Create First Invoice</button>
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Branch</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                    <tr>
                        <td style="font-weight:700;color:var(--accent)">
                            <a href="{{ route('invoices.show', $inv) }}" style="color:inherit;text-decoration:none">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td>{{ $inv->customer_name }}</td>
                        <td>{{ $inv->branch?->name ?? '—' }}</td>
                        <td style="font-weight:600">₱{{ number_format($inv->total, 2) }}</td>
                        <td><span class="status-pill {{ $inv->status }}">{{ ucfirst($inv->status) }}</span></td>
                        <td>{{ $inv->due_date ? $inv->due_date->format('M d, Y') : '—' }}</td>
                        <td>
                            <a href="{{ route('invoices.show', $inv) }}" style="font-size:12px;color:var(--accent);text-decoration:none">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $invoices->links() }}</div>
    @endif
</div>

<!-- Create Invoice Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal-content">
        <div class="modal-title">Create Invoice</div>
        <form action="{{ route('invoices.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-input" required>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Customer Name</label>
                    <input type="text" name="customer_name" class="form-input" placeholder="Customer name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Customer Email</label>
                    <input type="email" name="customer_email" class="form-input" placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Customer Phone</label>
                    <input type="text" name="customer_phone" class="form-input" placeholder="+63...">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" name="tax_rate" class="form-input" value="12" step="0.01" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Discount (₱)</label>
                    <input type="number" name="discount" class="form-input" value="0" step="0.01" min="0" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-input" placeholder="Optional notes">
                </div>
            </div>

            <div style="margin-top:20px">
                <label class="form-label" style="margin-bottom:10px;display:block">Items</label>
                <div class="inv-items-header">
                    <span>Description</span>
                    <span>Quantity</span>
                    <span>Unit Price</span>
                    <span></span>
                </div>
                <div id="invItems">
                    <div class="inv-item-row">
                        <input type="text" name="items[0][description]" class="form-input" placeholder="Item description" required>
                        <input type="number" name="items[0][quantity]" class="form-input" step="0.001" min="0.001" placeholder="Qty" required>
                        <input type="number" name="items[0][unit_price]" class="form-input" step="0.01" min="0" placeholder="₱ Price" required>
                        <button type="button" class="btn-remove-item" onclick="this.closest('.inv-item-row').remove()">×</button>
                    </div>
                </div>
                <button type="button" class="btn-add-item" onclick="addInvItem()">+ Add Item</button>
            </div>

            <button type="submit" class="btn-submit">Create Invoice</button>
        </form>
    </div>
</div>

<script>
    let itemIndex = 1;

    function addInvItem() {
        const container = document.getElementById('invItems');
        const row = document.createElement('div');
        row.className = 'inv-item-row';
        row.innerHTML = `
            <input type="text" name="items[${itemIndex}][description]" class="form-input" placeholder="Item description" required>
            <input type="number" name="items[${itemIndex}][quantity]" class="form-input" step="0.001" min="0.001" placeholder="Qty" required>
            <input type="number" name="items[${itemIndex}][unit_price]" class="form-input" step="0.01" min="0" placeholder="₱ Price" required>
            <button type="button" class="btn-remove-item" onclick="this.closest('.inv-item-row').remove()">×</button>
        `;
        container.appendChild(row);
        itemIndex++;
    }

    function openCreateModal() {
        document.getElementById('createModal').classList.add('active');
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.remove('active');
    }

    document.getElementById('createModal').addEventListener('click', function(e) {
        if (e.target === this) closeCreateModal();
    });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCreateModal(); });

    function setStatus(status) {
        const url = new URL(window.location.href);
        url.searchParams.set('status', status);
        window.location.href = url.toString();
    }

    function setBranch(branchId) {
        const url = new URL(window.location.href);
        if (branchId) {
            url.searchParams.set('branch_id', branchId);
        } else {
            url.searchParams.delete('branch_id');
        }
        window.location.href = url.toString();
    }
</script>
@endsection
