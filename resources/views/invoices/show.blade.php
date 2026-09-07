@extends('layouts.sidebar')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('styles')
    .inv-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    @media (max-width: 800px) { .inv-detail-grid { grid-template-columns: 1fr; } }

    .inv-info-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
    }
    .inv-info-panel__title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 16px;
    }
    .inv-info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }
    .inv-info-row:last-child { border-bottom: none; }
    .inv-info-row__label { color: var(--text-2); font-weight: 500; }
    .inv-info-row__value { font-weight: 600; color: var(--text); }

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

    .inv-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .inv-action-btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all .15s;
    }
    .inv-action-btn.primary { background: var(--accent); color: #fff; }
    .inv-action-btn.success { background: #16a34a; color: #fff; }
    .inv-action-btn.danger { background: #dc2626; color: #fff; }
    .inv-action-btn.secondary { background: #f3f4f6; color: #374151; border: 1px solid var(--border); }
    .inv-action-btn:hover { opacity: .9; }

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

    .invoice-print {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 40px;
        max-width: 800px;
        margin: 0 auto;
    }
    .invoice-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--border);
    }
    .invoice-title {
        font-size: 28px;
        font-weight: 800;
        color: var(--accent);
    }
    .invoice-number {
        font-size: 14px;
        color: var(--text-2);
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

<a href="{{ route('invoices.index') }}" class="back-link">← Back to Invoices</a>

<!-- Invoice Document -->
<div class="invoice-print">
    <div class="invoice-header">
        <div>
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
        </div>
        <div style="text-align:right">
            <div style="font-size:13px;color:var(--text-2)">{{ $invoice->branch?->name ?? '' }}</div>
            <div style="margin-top:8px">
                <span class="status-pill {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
            </div>
        </div>
    </div>

    <div class="inv-detail-grid">
        <div>
            <div style="font-size:11px;font-weight:600;color:var(--text-3);text-transform:uppercase;margin-bottom:8px">Bill To</div>
            <div style="font-size:14px;font-weight:700;color:var(--text)">{{ $invoice->customer_name }}</div>
            @if($invoice->customer_email)
                <div style="font-size:12px;color:var(--text-2)">{{ $invoice->customer_email }}</div>
            @endif
            @if($invoice->customer_phone)
                <div style="font-size:12px;color:var(--text-2)">{{ $invoice->customer_phone }}</div>
            @endif
            @if($invoice->customer_address)
                <div style="font-size:12px;color:var(--text-2)">{{ $invoice->customer_address }}</div>
            @endif
        </div>
        <div style="text-align:right">
            <div style="font-size:12px;color:var(--text-2);margin-bottom:4px">
                <strong>Date:</strong> {{ $invoice->created_at->format('M d, Y') }}
            </div>
            @if($invoice->due_date)
            <div style="font-size:12px;color:var(--text-2);margin-bottom:4px">
                <strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}
            </div>
            @endif
            @if($invoice->paid_at)
            <div style="font-size:12px;color:#15803d;font-weight:600">
                <strong>Paid:</strong> {{ $invoice->paid_at->format('M d, Y') }}
            </div>
            @endif
        </div>
    </div>

    <!-- Items Table -->
    <table class="data-table" style="margin:24px 0">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align:right">Qty</th>
                <th style="text-align:right">Unit Price</th>
                <th style="text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td style="font-weight:600">{{ $item->description }}</td>
                    <td style="text-align:right">{{ $item->quantity }}</td>
                    <td style="text-align:right">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align:right;font-weight:600">₱{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div style="display:flex;justify-content:flex-end">
        <div style="width:280px">
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px">
                <span style="color:var(--text-2)">Subtotal</span>
                <span style="font-weight:600">₱{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px">
                <span style="color:var(--text-2)">Tax ({{ $invoice->tax_rate }}%)</span>
                <span style="font-weight:600">₱{{ number_format($invoice->tax_amount, 2) }}</span>
            </div>
            @if($invoice->discount > 0)
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px">
                <span style="color:var(--text-2)">Discount</span>
                <span style="font-weight:600;color:#dc2626">-₱{{ number_format($invoice->discount, 2) }}</span>
            </div>
            @endif
            <div style="display:flex;justify-content:space-between;padding:12px 0;font-size:16px;font-weight:800;border-top:2px solid var(--border);margin-top:8px">
                <span>Total</span>
                <span style="color:var(--accent)">₱{{ number_format($invoice->total, 2) }}</span>
            </div>
        </div>
    </div>

    @if($invoice->notes)
    <div style="margin-top:24px;padding:16px;background:var(--bg);border-radius:8px">
        <div style="font-size:11px;font-weight:600;color:var(--text-3);text-transform:uppercase;margin-bottom:6px">Notes</div>
        <div style="font-size:12px;color:var(--text-2)">{{ $invoice->notes }}</div>
    </div>
    @endif
</div>

<!-- Status Actions -->
<div style="max-width:800px;margin:24px auto 0">
    @php
        $validTransitions = [
            'draft' => ['sent' => 'Send Invoice', 'cancelled' => 'Cancel'],
            'sent' => ['paid' => 'Mark as Paid', 'overdue' => 'Mark as Overdue', 'cancelled' => 'Cancel'],
            'paid' => [],
            'overdue' => ['paid' => 'Mark as Paid', 'cancelled' => 'Cancel'],
            'cancelled' => ['draft' => 'Reopen as Draft'],
        ];
        $transitions = $validTransitions[$invoice->status] ?? [];
    @endphp

    @if(!empty($transitions))
        <div class="inv-actions">
            @foreach($transitions as $status => $label)
                <form action="{{ route('invoices.update-status', $invoice) }}" method="POST" style="display:inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $status }}">
                    <button type="submit" class="inv-action-btn {{ $status === 'cancelled' ? 'danger' : ($status === 'paid' ? 'success' : 'primary') }}">
                        {{ $label }}
                    </button>
                </form>
            @endforeach
        </div>
    @endif

    @if($invoice->status === 'draft')
    <div style="margin-top:16px">
        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
              onsubmit="return confirm('Are you sure you want to delete {{ $invoice->invoice_number }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inv-action-btn danger">Delete Invoice</button>
        </form>
    </div>
    @endif
</div>
@endsection
