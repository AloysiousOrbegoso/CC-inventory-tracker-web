@extends('layouts.sidebar')

@section('title', 'Forecasting')

@section('styles')
    .fc-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .fc-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 20px;
    }
    .fc-card__label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-3);
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 8px;
    }
    .fc-card__value {
        font-size: 24px;
        font-weight: 800;
        color: var(--text);
        letter-spacing: -.02em;
    }
    .fc-card__sub {
        font-size: 12px;
        color: var(--text-2);
        margin-top: 4px;
    }
    .fc-trend-up { color: #16a34a; }
    .fc-trend-down { color: #dc2626; }
    .fc-trend-flat { color: var(--text-3); }

    .fc-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 28px;
    }
    @media (max-width: 900px) { .fc-grid { grid-template-columns: 1fr; } }

    .fc-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
    }
    .fc-panel__title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 16px;
    }

    .fc-projection-row {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 14px 0;
        border-bottom: 1px solid var(--border);
    }
    .fc-projection-row:last-child { border-bottom: none; }
    .fc-projection-month {
        font-size: 14px;
        font-weight: 700;
        color: var(--text);
        width: 100px;
        flex-shrink: 0;
    }
    .fc-projection-bars {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .fc-projection-bar {
        height: 8px;
        border-radius: 4px;
        transition: width .4s;
    }
    .fc-projection-bar.revenue { background: #3b82f6; }
    .fc-projection-bar.expenses { background: #f97316; }
    .fc-projection-values {
        text-align: right;
        width: 120px;
        flex-shrink: 0;
    }
    .fc-projection-profit {
        font-size: 14px;
        font-weight: 700;
    }
    .fc-projection-profit.positive { color: #16a34a; }
    .fc-projection-profit.negative { color: #dc2626; }
    .fc-confidence {
        font-size: 11px;
        color: var(--text-3);
    }

    .fc-needs-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .fc-needs-card__title {
        font-size: 14px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .fc-needs-card__value {
        font-size: 16px;
        font-weight: 600;
        color: var(--accent);
    }

    .fc-filters {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        align-items: center;
    }
    .fc-filter-select {
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        font-family: var(--font);
    }
    .fc-filter-btn {
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        cursor: pointer;
    }
    .fc-filter-btn.active {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    .fc-historical-table {
        margin-top: 20px;
    }
@endsection

@section('content')
<!-- Filters -->
<div class="fc-filters">
    @foreach([3 => '3 Months', 6 => '6 Months', 12 => '12 Months'] as $m => $label)
        <button class="fc-filter-btn {{ $months == $m ? 'active' : '' }}"
                onclick="setMonths({{ $m }})">{{ $label }}</button>
    @endforeach

    @if($branches->count() > 1)
        <select class="fc-filter-select" onchange="setBranch(this.value)">
            <option value="">All Branches</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    @endif
</div>

<!-- Summary Cards -->
<div class="fc-summary">
    <div class="fc-card">
        <div class="fc-card__label">Revenue Trend</div>
        <div class="fc-card__value {{ $revenueTrend > 0 ? 'fc-trend-up' : ($revenueTrend < 0 ? 'fc-trend-down' : 'fc-trend-flat') }}">
            {{ $revenueTrend > 0 ? '+' : '' }}₱{{ number_format($revenueTrend, 0) }}/mo
        </div>
        <div class="fc-card__sub">Monthly change rate</div>
    </div>
    <div class="fc-card">
        <div class="fc-card__label">Expense Trend</div>
        <div class="fc-card__value {{ $expenseTrend > 0 ? 'fc-trend-down' : ($expenseTrend < 0 ? 'fc-trend-up' : 'fc-trend-flat') }}">
            {{ $expenseTrend > 0 ? '+' : '' }}₱{{ number_format($expenseTrend, 0) }}/mo
        </div>
        <div class="fc-card__sub">Monthly change rate</div>
    </div>
    <div class="fc-card">
        <div class="fc-card__label">Daily Transactions</div>
        <div class="fc-card__value">{{ $avgDailyTransactions }}</div>
        <div class="fc-card__sub">30-day average</div>
    </div>
    <div class="fc-card">
        <div class="fc-card__label">Monthly Supply Cost</div>
        <div class="fc-card__value">₱{{ number_format($projectedSupplyCost, 0) }}</div>
        <div class="fc-card__sub">Projected next month (+5%)</div>
    </div>
</div>

<!-- Projections & Needs -->
<div class="fc-grid">
    <!-- Revenue Projections -->
    <div class="fc-panel">
        <div class="fc-panel__title">📈 3-Month Revenue Projection</div>
        @php
            $maxVal = max(1, ...collect($projections)->flatMap(fn($p) => [$p['revenue'], $p['expenses']])->toArray());
        @endphp
        @foreach($projections as $p)
            <div class="fc-projection-row">
                <div class="fc-projection-month">{{ $p['month'] }}</div>
                <div class="fc-projection-bars">
                    <div class="fc-projection-bar revenue" style="width:{{ ($p['revenue'] / $maxVal) * 100 }}%"></div>
                    <div class="fc-projection-bar expenses" style="width:{{ ($p['expenses'] / $maxVal) * 100 }}%"></div>
                </div>
                <div class="fc-projection-values">
                    <div class="fc-projection-profit {{ $p['profit'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $p['profit'] >= 0 ? '+' : '-' }}₱{{ number_format(abs($p['profit']), 0) }}
                    </div>
                    <div class="fc-confidence">{{ $p['confidence'] }}% confidence</div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Business Needs -->
    <div>
        <div class="fc-needs-card">
            <div class="fc-needs-card__title">👥 Staffing Needs</div>
            <div class="fc-needs-card__value">{{ $staffingNeeds }}</div>
            <div style="font-size:12px;color:var(--text-2);margin-top:8px">
                Based on {{ $avgDailyTransactions }} avg daily transactions
            </div>
        </div>

        <div class="fc-needs-card">
            <div class="fc-needs-card__title">📦 Inventory Budget</div>
            <div class="fc-needs-card__value">₱{{ number_format($projectedSupplyCost, 2) }}</div>
            <div style="font-size:12px;color:var(--text-2);margin-top:8px">
                Projected supply cost for next month (5% buffer included)
            </div>
        </div>

        <div class="fc-needs-card">
            <div class="fc-needs-card__title">💰 Cash Reserve Recommendation</div>
            @php
                $avgMonthlyExpenses = collect($historical)->pluck('expenses')->avg();
                $reserveNeeded = $avgMonthlyExpenses * 2; // 2 months buffer
            @endphp
            <div class="fc-needs-card__value">₱{{ number_format($reserveNeeded, 0) }}</div>
            <div style="font-size:12px;color:var(--text-2);margin-top:8px">
                Recommended 2-month expense buffer
            </div>
        </div>
    </div>
</div>

<!-- Historical Data Table -->
<div class="fc-panel fc-historical-table">
    <div class="fc-panel__title">📊 Historical Performance</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Month</th>
                <th style="text-align:right">Revenue</th>
                <th style="text-align:right">Expenses</th>
                <th style="text-align:right">Profit</th>
                <th style="text-align:right">Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historical as $h)
                <tr>
                    <td style="font-weight:600">{{ $h['month'] }}</td>
                    <td style="text-align:right;color:#3b82f6;font-weight:600">₱{{ number_format($h['revenue'], 2) }}</td>
                    <td style="text-align:right;color:#f97316;font-weight:600">₱{{ number_format($h['expenses'], 2) }}</td>
                    <td style="text-align:right;font-weight:700;color:{{ $h['profit'] >= 0 ? '#16a34a' : '#dc2626' }}">
                        {{ $h['profit'] >= 0 ? '+' : '-' }}₱{{ number_format(abs($h['profit']), 2) }}
                    </td>
                    <td style="text-align:right">
                        {{ $h['revenue'] > 0 ? number_format(($h['profit'] / $h['revenue']) * 100, 1) : '0.0' }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    function setMonths(months) {
        const url = new URL(window.location.href);
        url.searchParams.set('months', months);
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
