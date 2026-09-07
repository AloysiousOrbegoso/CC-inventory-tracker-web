@extends('layouts.sidebar')

@section('title', 'PO ' . $po->po_number)

@section('styles')
    .po-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    @media (max-width: 800px) { .po-detail-grid { grid-template-columns: 1fr; } }

    .po-info-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
    }
    .po-info-panel__title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 16px;
    }
    .po-info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }
    .po-info-row:last-child { border-bottom: none; }
    .po-info-row__label { color: var(--text-2); font-weight: 500; }
    .po-info-row__value { font-weight: 600; color: var(--text); }

    .status-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pill.draft { background: #f3f4f6; color: #374151; }
    .status-pill.pending { background: #fefce8; color: #a16207; }
    .status-pill.approved { background: #dbeafe; color: #1d4ed8; }
    .status-pill.ordered { background: #e0e7ff; color: #4338ca; }
    .status-pill.delivered { background: #fef3c7; color: #b45309; }
    .status-pill.received { background: #dcfce7; color: #15803d; }
    .status-pill.cancelled { background: #fee2e2; color: #dc2626; }

    .po-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .po-action-btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all .15s;
    }
    .po-action-btn.primary { background: var(--accent); color: #fff; }
    .po-action-btn.success { background: #16a34a; color: #fff; }
    .po-action-btn.warning { background: #f97316; color: #fff; }
    .po-action-btn.danger { background: #dc2626; color: #fff; }
    .po-action-btn.secondary { background: #f3f4f6; color: #374151; border: 1px solid var(--border); }
    .po-action-btn:hover { opacity: .9; }

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

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-2);
        text-decoration: none;
        margin-bottom: 16px;
    }
    .back-link:hover { color: var(--accent); }
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

<a href="{{ route('purchase-orders.index') }}" class="back-link">← Back to Purchase Orders</a>

<div class="po-detail-grid">
    <!-- PO Info -->
    <div class="po-info-panel">
        <div class="po-info-panel__title">Purchase Order Details</div>
        <div class="po-info-row">
            <span class="po-info-row__label">PO Number</span>
            <span class="po-info-row__value" style="color:var(--accent);font-weight:700">{{ $po->po_number }}</span>
        </div>
        <div class="po-info-row">
            <span class="po-info-row__label">Status</span>
            <span class="po-info-row__value"><span class="status-pill {{ $po->status }}">{{ ucfirst($po->status) }}</span></span>
        </div>
        <div class="po-info-row">
            <span class="po-info-row__label">Supplier</span>
            <span class="po-info-row__value">{{ $po->supplier?->name ?? '—' }}</span>
        </div>
        <div class="po-info-row">
            <span class="po-info-row__label">Branch</span>
            <span class="po-info-row__value">{{ $po->branch?->name ?? '—' }}</span>
        </div>
        <div class="po-info-row">
            <span class="po-info-row__label">Created By</span>
            <span class="po-info-row__value">{{ $po->creator?->name ?? '—' }}</span>
        </div>
        <div class="po-info-row">
            <span class="po-info-row__label">Expected Delivery</span>
            <span class="po-info-row__value">{{ $po->expected_delivery ? $po->expected_delivery->format('M d, Y') : 'Not set' }}</span>
        </div>
        @if($po->delivered_at)
        <div class="po-info-row">
            <span class="po-info-row__label">Delivered On</span>
            <span class="po-info-row__value">{{ $po->delivered_at->format('M d, Y') }}</span>
        </div>
        @endif
        @if($po->notes)
        <div class="po-info-row">
            <span class="po-info-row__label">Notes</span>
            <span class="po-info-row__value">{{ $po->notes }}</span>
        </div>
        @endif
    </div>

    <!-- Actions Panel -->
    <div class="po-info-panel">
        <div class="po-info-panel__title">Status Workflow</div>
        <div style="font-size:13px;color:var(--text-2);margin-bottom:16px">
            Current: <strong>{{ ucfirst($po->status) }}</strong>
        </div>

        @php
            $validTransitions = [
                'draft' => ['pending' => 'Submit for Approval', 'cancelled' => 'Cancel'],
                'pending' => ['approved' => 'Approve', 'cancelled' => 'Cancel'],
                'approved' => ['ordered' => 'Mark as Ordered', 'cancelled' => 'Cancel'],
                'ordered' => ['delivered' => 'Mark as Delivered'],
                'delivered' => ['received' => 'Mark as Received'],
                'received' => [],
                'cancelled' => ['draft' => 'Reopen as Draft'],
            ];
            $transitions = $validTransitions[$po->status] ?? [];
        @endphp

        @if(!empty($transitions))
            <div class="po-actions">
                @foreach($transitions as $status => $label)
                    <form action="{{ route('purchase-orders.update-status', $po) }}" method="POST" style="display:inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status }}">
                        <button type="submit" class="po-action-btn
                            {{ $status === 'cancelled' ? 'danger' : ($status === 'received' ? 'success' : 'primary') }}">
                            {{ $label }}
                        </button>
                    </form>
                @endforeach
            </div>
        @else
            <div style="font-size:13px;color:var(--text-3)">No further actions available.</div>
        @endif
    </div>
</div>

<!-- Items Table -->
<div class="po-info-panel">
    <div class="po-info-panel__title">Order Items</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Ingredient</th>
                <th style="text-align:right">Quantity</th>
                <th style="text-align:right">Unit Price</th>
                <th style="text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po->items as $item)
                <tr>
                    <td style="font-weight:600">{{ $item->ingredient?->name ?? 'Unknown' }}</td>
                    <td style="text-align:right">{{ $item->quantity }} {{ $item->ingredient?->unit ?? '' }}</td>
                    <td style="text-align:right">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align:right;font-weight:700">₱{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:var(--color-accent-light)">
                <td colspan="3" style="font-weight:700">Total</td>
                <td style="text-align:right;font-weight:800;font-size:14px;color:var(--accent)">₱{{ number_format($po->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Delete Button (only for draft/pending) -->
@if(in_array($po->status, ['draft', 'pending']))
<div style="margin-top:20px">
    <form action="{{ route('purchase-orders.destroy', $po) }}" method="POST"
          onsubmit="return confirm('Are you sure you want to delete {{ $po->po_number }}?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="po-action-btn danger">Delete Purchase Order</button>
    </form>
</div>
@endif
@endsection
