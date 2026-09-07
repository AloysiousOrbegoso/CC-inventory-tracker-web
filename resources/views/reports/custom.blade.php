@extends('layouts.sidebar')

@section('title', 'Custom Reports')

@section('styles')
    .report-form {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .form-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px; }
    @media (max-width: 900px) { .form-row { grid-template-columns: 1fr 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-label { font-size: 12px; font-weight: 600; color: var(--accent); }
    .form-input { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: var(--font); }
    .btn-submit { padding: 10px 24px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; background: var(--accent); color: #fff; cursor: pointer; }

    .report-result {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
    }
    .report-title { font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
    .report-period { font-size: 13px; color: var(--text-2); margin-bottom: 20px; }

    .report-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .report-card { background: var(--bg); border-radius: 12px; padding: 16px; text-align: center; }
    .report-card__value { font-size: 24px; font-weight: 800; color: var(--accent); }
    .report-card__label { font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }
@endsection

@section('content')
<div class="report-form">
    <form action="{{ route('reports.custom.generate') }}" method="POST">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Report Type</label>
                <select name="report_type" class="form-input" required>
                    <option value="revenue">Revenue</option>
                    <option value="expenses">Expenses</option>
                    <option value="inventory">Inventory</option>
                    <option value="staff">Staff</option>
                    <option value="customers">Customers</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-input">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-input" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required>
            </div>
        </div>
        <button type="submit" class="btn-submit">Generate Report</button>
    </form>
</div>

@if($reportData)
<div class="report-result">
    <div class="report-title">{{ $reportData['title'] }}</div>
    @if(isset($reportData['period']))
        <div class="report-period">{{ $reportData['period'] }}</div>
    @endif

    <div class="report-cards">
        @foreach($reportData['summary'] as $key => $value)
            @if(!is_array($value))
            <div class="report-card">
                <div class="report-card__value">
                    @if(in_array($key, ['total_revenue', 'total_expenses', 'total_spent', 'avg_spent', 'avg_transaction', 'total_value']))
                        ₱{{ number_format($value, 2) }}
                    @else
                        {{ $value }}
                    @endif
                </div>
                <div class="report-card__label">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
            </div>
            @endif
        @endforeach
    </div>

    @if(isset($reportData['by_category']))
    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px">By Category</h3>
    <table class="data-table">
        <thead><tr><th>Category</th><th style="text-align:right">Amount</th></tr></thead>
        <tbody>
            @foreach($reportData['by_category'] as $cat => $amount)
                <tr>
                    <td style="text-transform:capitalize">{{ $cat }}</td>
                    <td style="text-align:right;font-weight:600">₱{{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($reportData['by_tier']))
    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px">By Loyalty Tier</h3>
    <table class="data-table">
        <thead><tr><th>Tier</th><th style="text-align:right">Count</th></tr></thead>
        <tbody>
            @foreach($reportData['by_tier'] as $tier => $count)
                <tr>
                    <td style="text-transform:capitalize">{{ $tier }}</td>
                    <td style="text-align:right;font-weight:600">{{ $count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($reportData['staff']))
    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px">Staff List</h3>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Role</th><th>Branch</th></tr></thead>
        <tbody>
            @foreach($reportData['staff'] as $s)
                <tr>
                    <td style="font-weight:600">{{ $s->name }}</td>
                    <td style="text-transform:capitalize">{{ $s->role }}</td>
                    <td>{{ $s->branch?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endif
@endsection
