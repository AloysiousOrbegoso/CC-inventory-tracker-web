@extends('layouts.sidebar')

@section('title', 'Purchase Orders')

@section('styles')
    /* ═══ OPERATIONS WORKFLOW STYLE ═══ */
    .po-grid {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 20px;
    }
    @media (max-width: 1024px) { .po-grid { grid-template-columns: 1fr; } }

    /* Workflow pipeline — visual status progression */
    .po-pipeline {
        display: flex;
        gap: 4px;
        margin-bottom: 24px;
        background: #f9fafb;
        padding: 6px;
        border-radius: 12px;
    }
    .po-pipeline-stage {
        flex: 1;
        padding: 12px 8px;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all .15s;
        background: transparent;
        border: none;
    }
    .po-pipeline-stage:hover { background: #fff; }
    .po-pipeline-stage.active { background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    .po-pipeline-stage__count {
        font-size: 20px;
        font-weight: 800;
        color: var(--text);
    }
    .po-pipeline-stage__label {
        font-size: 10px;
        font-weight: 600;
        color: var(--text-3);
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-top: 2px;
    }
    .po-pipeline-stage.active .po-pipeline-stage__count { color: var(--accent); }

    /* PO Cards — workflow items */
    .po-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all .15s;
    }
    .po-card:hover { border-color: var(--accent); }
    .po-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .po-card-number {
        font-size: 14px;
        font-weight: 700;
        color: var(--accent);
    }
    .po-card-supplier {
        font-size: 12px;
        color: var(--text-2);
        margin-top: 2px;
    }
    .po-card-status {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .po-card-status.draft { background: #f3f4f6; color: #374151; }
    .po-card-status.pending { background: #fefce8; color: #a16207; }
    .po-card-status.approved { background: #dbeafe; color: #1d4ed8; }
    .po-card-status.ordered { background: #e0e7ff; color: #4338ca; }
    .po-card-status.delivered { background: #fef3c7; color: #b45309; }
    .po-card-status.received { background: #dcfce7; color: #15803d; }
    .po-card-status.cancelled { background: #fee2e2; color: #dc2626; }

    .po-card-meta {
        display: flex;
        gap: 16px;
        font-size: 12px;
        color: var(--text-2);
        margin-bottom: 12px;
    }
    .po-card-meta span { display: flex; align-items: center; gap: 4px; }

    .po-card-items {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
    }
    .po-card-item {
        padding: 4px 8px;
        background: #f3f4f6;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-2);
    }

    .po-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 12px;
        border-top: 1px solid var(--border);
    }
    .po-card-total {
        font-size: 16px;
        font-weight: 800;
        color: var(--text);
    }
    .po-card-actions {
        display: flex;
        gap: 6px;
    }
    .po-action-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        cursor: pointer;
        text-decoration: none;
        transition: all .15s;
    }
    .po-action-btn:hover { border-color: var(--accent); color: var(--accent); }
    .po-action-btn.primary { background: var(--accent); color: #fff; border-color: var(--accent); }
    .po-action-btn.primary:hover { opacity: .9; }

    /* Sidebar */
    .po-side-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
    }
    .po-side-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 12px;
    }

    /* Filter bar */
    .po-filter-bar {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        align-items: center;
    }
    .po-filter-select {
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        font-family: var(--font);
    }

    /* Modal */
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
    .modal-title { font-size: 20px; font-weight: 700; color: var(--text); margin-bottom: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-label { font-size: 12px; font-weight: 600; color: var(--accent); }
    .form-input { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: var(--font); }

    .po-items-header {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 40px;
        gap: 10px;
        margin-bottom: 8px;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-3);
        text-transform: uppercase;
    }
    .po-item-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 40px;
        gap: 10px;
        margin-bottom: 8px;
        align-items: center;
    }
    .po-item-row .form-input { padding: 8px 10px; font-size: 12px; }
    .btn-remove-item { width: 32px; height: 32px; border-radius: 6px; border: none; background: #fee2e2; color: #dc2626; cursor: pointer; font-size: 14px; }
    .btn-add-item { padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; border: 1px dashed var(--border); background: transparent; color: var(--accent); cursor: pointer; margin-top: 8px; }
    .btn-submit { margin-top: 20px; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; background: #16a34a; color: #fff; cursor: pointer; }

    .flash-success { background: #dcfce7; border: 1px solid #16a34a; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    .flash-error { background: #fee2e2; border: 1px solid #dc2626; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }

    .empty-state { text-align: center; color: var(--text-3); font-size: 13px; padding: 40px 20px; }
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

{{-- ═══ FILTER BAR ═══ --}}
<div class="po-filter-bar">
    @if($branches->count() > 1)
        <select class="po-filter-select" onchange="setBranch(this.value)">
            <option value="">All Branches</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    @endif
    @if(auth()->user()->isManager())
    <button class="po-action-btn primary" style="margin-left:auto" onclick="openCreateModal()">+ New Purchase Order</button>
    @endif
</div>

{{-- ═══ WORKFLOW PIPELINE ═══ --}}
<div class="po-pipeline">
    <button class="po-pipeline-stage {{ ($status ?? 'all') === 'all' ? 'active' : '' }}" onclick="setStatus('all')">
        <div class="po-pipeline-stage__count">{{ $stats['total'] }}</div>
        <div class="po-pipeline-stage__label">All</div>
    </button>
    <button class="po-pipeline-stage {{ ($status ?? '') === 'draft' ? 'active' : '' }}" onclick="setStatus('draft')">
        <div class="po-pipeline-stage__count">{{ $stats['draft'] ?? 0 }}</div>
        <div class="po-pipeline-stage__label">Draft</div>
    </button>
    <button class="po-pipeline-stage {{ ($status ?? '') === 'pending' ? 'active' : '' }}" onclick="setStatus('pending')">
        <div class="po-pipeline-stage__count">{{ $stats['pending'] }}</div>
        <div class="po-pipeline-stage__label">Pending</div>
    </button>
    <button class="po-pipeline-stage {{ ($status ?? '') === 'approved' ? 'active' : '' }}" onclick="setStatus('approved')">
        <div class="po-pipeline-stage__count">{{ $stats['approved'] }}</div>
        <div class="po-pipeline-stage__label">Approved</div>
    </button>
    <button class="po-pipeline-stage {{ ($status ?? '') === 'ordered' ? 'active' : '' }}" onclick="setStatus('ordered')">
        <div class="po-pipeline-stage__count">{{ $stats['ordered'] ?? 0 }}</div>
        <div class="po-pipeline-stage__label">Ordered</div>
    </button>
    <button class="po-pipeline-stage {{ ($status ?? '') === 'delivered' ? 'active' : '' }}" onclick="setStatus('delivered')">
        <div class="po-pipeline-stage__count">{{ $stats['delivered'] }}</div>
        <div class="po-pipeline-stage__label">Delivered</div>
    </button>
    <button class="po-pipeline-stage {{ ($status ?? '') === 'received' ? 'active' : '' }}" onclick="setStatus('received')">
        <div class="po-pipeline-stage__count">{{ $stats['received'] ?? 0 }}</div>
        <div class="po-pipeline-stage__label">Received</div>
    </button>
</div>

{{-- ═══ MAIN CONTENT ═══ --}}
<div class="po-grid">
    <div>
        @if($purchaseOrders->isEmpty())
            <div class="po-side-panel">
                <div class="empty-state" style="padding:20px">
                    <div style="font-size:24px;margin-bottom:8px">📋</div>
                    <div style="font-weight:700;color:var(--text);margin-bottom:4px">No Purchase Orders Yet</div>
                    <div>Create your first PO to start ordering from suppliers.</div>
                    <div style="margin-top:16px;padding:12px;background:var(--bg);border-radius:8px;text-align:left;font-size:12px;color:var(--text-2)">
                        <div style="font-weight:600;color:var(--text);margin-bottom:6px">📋 PO Workflow:</div>
                        <div><span style="color:var(--accent);font-weight:600">1. Draft</span> → Create PO with items</div>
                        <div><span style="color:var(--accent);font-weight:600">2. Pending</span> → Submit for approval</div>
                        <div><span style="color:var(--accent);font-weight:600">3. Approved</span> → Ready to order</div>
                        <div><span style="color:var(--accent);font-weight:600">4. Ordered</span> → Sent to supplier</div>
                        <div><span style="color:var(--accent);font-weight:600">5. Delivered</span> → Received at branch</div>
                        <div><span style="color:var(--accent);font-weight:600">6. Received</span> → Stock updated</div>
                    </div>
                    <button class="po-action-btn primary" style="margin-top:16px" onclick="openCreateModal()">+ Create First PO</button>
                </div>
            </div>
        @else
            @foreach($purchaseOrders as $po)
                <div class="po-card">
                    <div class="po-card-header">
                        <div>
                            <a href="{{ route('purchase-orders.show', $po) }}" class="po-card-number" style="text-decoration:none">{{ $po->po_number }}</a>
                            <div class="po-card-supplier">{{ $po->supplier?->name ?? '—' }}</div>
                        </div>
                        <span class="po-card-status {{ $po->status }}">{{ ucfirst($po->status) }}</span>
                    </div>
                    <div class="po-card-meta">
                        <span>📍 {{ $po->branch?->name ?? '—' }}</span>
                        <span>📦 {{ $po->items->count() }} items</span>
                        @if($po->expected_delivery)
                            <span>📅 {{ $po->expected_delivery->format('M d, Y') }}</span>
                        @endif
                    </div>
                    <div class="po-card-items">
                        @foreach($po->items->take(3) as $item)
                            <span class="po-card-item">{{ $item->ingredient?->name ?? '—' }}</span>
                        @endforeach
                        @if($po->items->count() > 3)
                            <span class="po-card-item">+{{ $po->items->count() - 3 }} more</span>
                        @endif
                    </div>
                    <div class="po-card-footer">
                        <div class="po-card-total">₱{{ number_format($po->total_amount, 2) }}</div>
                        <div class="po-card-actions">
                            <a href="{{ route('purchase-orders.show', $po) }}" class="po-action-btn">View Details</a>
                            @if($po->status === 'draft')
                                <form action="{{ route('purchase-orders.update-status', $po) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="pending">
                                    <button type="submit" class="po-action-btn primary">Submit for Approval</button>
                                </form>
                            @elseif($po->status === 'pending')
                                <form action="{{ route('purchase-orders.update-status', $po) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="po-action-btn primary">Approve</button>
                                </form>
                            @elseif($po->status === 'approved')
                                <form action="{{ route('purchase-orders.update-status', $po) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="ordered">
                                    <button type="submit" class="po-action-btn primary">Mark as Ordered</button>
                                </form>
                            @elseif($po->status === 'ordered')
                                <form action="{{ route('purchase-orders.update-status', $po) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="delivered">
                                    <button type="submit" class="po-action-btn primary">Mark as Delivered</button>
                                </form>
                            @elseif($po->status === 'delivered')
                                <form action="{{ route('purchase-orders.update-status', $po) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="received">
                                    <button type="submit" class="po-action-btn primary">Confirm Receipt</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            <div style="margin-top:16px">{{ $purchaseOrders->links() }}</div>
        @endif
    </div>

    {{-- ═══ SIDEBAR ═══ --}}
    <div>
        {{-- Pending Actions --}}
        <div class="po-side-panel">
            <div class="po-side-title">⚡ Needs Action</div>
            @php
                $pendingPOs = $purchaseOrders->filter(fn($po) => in_array($po->status, ['pending', 'delivered']));
            @endphp
            @if($pendingPOs->isEmpty())
                <div style="text-align:center;color:var(--text-3);padding:16px;font-size:12px">No pending actions.</div>
            @else
                @foreach($pendingPOs->take(5) as $po)
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
                        <div style="width:8px;height:8px;border-radius:50%;background:{{ $po->status === 'pending' ? '#f97316' : '#16a34a' }}"></div>
                        <div style="flex:1">
                            <div style="font-size:12px;font-weight:600;color:var(--text)">{{ $po->po_number }}</div>
                            <div style="font-size:11px;color:var(--text-3)">{{ $po->supplier?->name ?? '—' }}</div>
                        </div>
                        <div style="font-size:11px;font-weight:600;color:var(--accent)">
                            {{ $po->status === 'pending' ? 'Approve' : 'Receive' }}
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Recent Activity --}}
        <div class="po-side-panel">
            <div class="po-side-title">📋 Recent POs</div>
            @foreach($purchaseOrders->take(5) as $po)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
                    <span class="po-card-status {{ $po->status }}" style="font-size:10px;padding:2px 6px">{{ ucfirst($po->status) }}</span>
                    <div style="flex:1">
                        <div style="font-size:12px;font-weight:600;color:var(--text)">{{ $po->po_number }}</div>
                        <div style="font-size:11px;color:var(--text-3)">₱{{ number_format($po->total_amount, 0) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Quick Stats --}}
        <div class="po-side-panel">
            <div class="po-side-title">📊 Summary</div>
            <div style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:12px;color:var(--text-2)">Total Value</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text)">₱{{ number_format($purchaseOrders->sum('total_amount'), 0) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:12px;color:var(--text-2)">This Month</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text)">{{ $purchaseOrders->where('created_at', '>=', now()->startOfMonth())->count() }} POs</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ CREATE PO MODAL ═══ --}}
<div class="modal-overlay" id="createModal">
    <div class="modal-content">
        <div class="modal-title">Create Purchase Order</div>
        <form action="{{ route('purchase-orders.store') }}" method="POST">
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
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-input" required>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Expected Delivery</label>
                    <input type="date" name="expected_delivery" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-input" placeholder="Optional notes">
                </div>
            </div>

            <div style="margin-top:20px">
                <label class="form-label" style="margin-bottom:10px;display:block">Items</label>
                <div class="po-items-header">
                    <span>Ingredient</span>
                    <span>Quantity</span>
                    <span>Unit Price</span>
                    <span></span>
                </div>
                <div id="poItems">
                    <div class="po-item-row">
                        <select name="items[0][ingredient_id]" class="form-input" required>
                            <option value="">Select ingredient</option>
                            @foreach($ingredients as $ing)
                                <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                            @endforeach
                        </select>
                        <input type="number" name="items[0][quantity]" class="form-input" step="0.001" min="0.001" placeholder="Qty" required>
                        <input type="number" name="items[0][unit_price]" class="form-input" step="0.01" min="0" placeholder="₱ Price" required>
                        <button type="button" class="btn-remove-item" onclick="this.closest('.po-item-row').remove()">×</button>
                    </div>
                </div>
                <button type="button" class="btn-add-item" onclick="addPoItem()">+ Add Item</button>
            </div>

            <button type="submit" class="btn-submit">Create Purchase Order</button>
        </form>
    </div>
</div>

<script>
    let itemIndex = 1;

    function addPoItem() {
        const container = document.getElementById('poItems');
        const ingredientOptions = `{!! $ingredients->map(fn($i) => '<option value=\"' . $i->id . '\">' . $i->name . ' (' . $i->unit . ')</option>')->join('') !!}`;

        const row = document.createElement('div');
        row.className = 'po-item-row';
        row.innerHTML = `
            <select name="items[${itemIndex}][ingredient_id]" class="form-input" required>
                <option value="">Select ingredient</option>
                ${ingredientOptions}
            </select>
            <input type="number" name="items[${itemIndex}][quantity]" class="form-input" step="0.001" min="0.001" placeholder="Qty" required>
            <input type="number" name="items[${itemIndex}][unit_price]" class="form-input" step="0.01" min="0" placeholder="₱ Price" required>
            <button type="button" class="btn-remove-item" onclick="this.closest('.po-item-row').remove()">×</button>
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
