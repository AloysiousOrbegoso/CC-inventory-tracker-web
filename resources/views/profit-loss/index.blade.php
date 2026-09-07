@extends('layouts.sidebar')

@section('title', 'Profit & Loss')

@section('styles')
    .pl-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .pl-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 20px;
    }
    .pl-card__label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-3);
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 8px;
    }
    .pl-card__value {
        font-size: 26px;
        font-weight: 800;
        color: var(--text);
        letter-spacing: -.02em;
    }
    .pl-card__value.profit { color: #16a34a; }
    .pl-card__value.loss { color: #dc2626; }
    .pl-card__sub {
        font-size: 12px;
        color: var(--text-2);
        margin-top: 4px;
    }

    .pl-grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 24px;
        margin-bottom: 28px;
    }
    @media (max-width: 900px) { .pl-grid { grid-template-columns: 1fr; } }

    .pl-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
    }
    .pl-panel__title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 16px;
    }

    /* Bar chart */
    .pl-chart {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        height: 180px;
        padding-top: 10px;
    }
    .pl-chart__col {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        height: 100%;
        justify-content: flex-end;
    }
    .pl-chart__bars {
        display: flex;
        gap: 3px;
        align-items: flex-end;
        width: 100%;
    }
    .pl-chart__bar {
        flex: 1;
        border-radius: 4px 4px 0 0;
        min-height: 2px;
        transition: height .3s;
    }
    .pl-chart__bar.revenue { background: #3b82f6; }
    .pl-chart__bar.expenses { background: #f97316; }
    .pl-chart__bar.profit-pos { background: #16a34a; }
    .pl-chart__bar.profit-neg { background: #dc2626; }
    .pl-chart__label {
        font-size: 10px;
        font-weight: 600;
        color: var(--text-3);
        text-align: center;
    }

    /* Expense breakdown */
    .pl-expense-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .pl-expense-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .pl-expense-row__label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
        width: 120px;
        flex-shrink: 0;
        text-transform: capitalize;
    }
    .pl-expense-row__bar-bg {
        flex: 1;
        height: 10px;
        background: var(--bg);
        border-radius: 6px;
        overflow: hidden;
    }
    .pl-expense-row__bar {
        height: 100%;
        border-radius: 6px;
        background: var(--accent);
        transition: width .4s;
    }
    .pl-expense-row__value {
        font-size: 13px;
        font-weight: 700;
        color: var(--text);
        width: 100px;
        text-align: right;
        flex-shrink: 0;
    }

    .pl-filters {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 24px;
        align-items: center;
    }
    .pl-filter-btn {
        padding: 7px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        cursor: pointer;
        transition: all .15s;
    }
    .pl-filter-btn:hover { border-color: var(--accent); }
    .pl-filter-btn.active {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }
    .pl-filter-select {
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        font-family: var(--font);
    }

    .pl-legend {
        display: flex;
        gap: 16px;
        margin-bottom: 12px;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-2);
    }
    .pl-legend__dot {
        width: 10px;
        height: 10px;
        border-radius: 3px;
        display: inline-block;
        margin-right: 4px;
        vertical-align: middle;
    }
@endsection

@section('content')
<div class="pl-filters">
    @foreach(['month' => 'This Month', 'quarter' => 'This Quarter', 'year' => 'This Year', 'all' => 'All Time'] as $key => $label)
        <button class="pl-filter-btn {{ $period === $key ? 'active' : '' }}"
                onclick="setPeriod('{{ $key }}')">{{ $label }}</button>
    @endforeach

    @if(!$isManager && $branches->count() > 1)
        <select class="pl-filter-select" onchange="setBranch(this.value)">
            <option value="">All Branches</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    @endif

    <span style="margin-left:auto; font-size:13px; font-weight:600; color:var(--text-2);">{{ $periodLabel }}</span>
</div>

<!-- Summary Cards -->
<div class="pl-summary">
    <div class="pl-card">
        <div class="pl-card__label">Total Revenue</div>
        <div class="pl-card__value">₱{{ number_format($totalRevenue, 2) }}</div>
        <div class="pl-card__sub">Sales income</div>
    </div>
    <div class="pl-card">
        <div class="pl-card__label">Total Expenses</div>
        <div class="pl-card__value" style="color:#dc2626">₱{{ number_format($totalExpenses, 2) }}</div>
        <div class="pl-card__sub">Overhead + Salary + Supplies</div>
    </div>
    <div class="pl-card">
        <div class="pl-card__label">Net Profit</div>
        <div class="pl-card__value {{ $netProfit >= 0 ? 'profit' : 'loss' }}">
            {{ $netProfit >= 0 ? '+' : '-' }}₱{{ number_format(abs($netProfit), 2) }}
        </div>
        <div class="pl-card__sub">{{ number_format($profitMargin, 1) }}% margin</div>
    </div>
</div>

<!-- Charts & Breakdown -->
<div class="pl-grid">
    <!-- Monthly Trend -->
    <div class="pl-panel">
        <div class="pl-panel__title">6-Month Trend</div>
        <div class="pl-legend">
            <span><span class="pl-legend__dot" style="background:#3b82f6"></span>Revenue</span>
            <span><span class="pl-legend__dot" style="background:#f97316"></span>Expenses</span>
            <span><span class="pl-legend__dot" style="background:#16a34a"></span>Profit</span>
        </div>
        @php
            $allValues = collect($monthlyTrend)->flatMap(fn($m) => [$m['revenue'], $m['expenses'], abs($m['profit'])])->toArray();
            $maxVal = empty($allValues) || max($allValues) == 0 ? 1 : max($allValues);
        @endphp
        <div class="pl-chart">
            @foreach($monthlyTrend as $m)
                <div class="pl-chart__col">
                    <div class="pl-chart__bars">
                        <div class="pl-chart__bar revenue" style="height:{{ ($m['revenue'] / $maxVal) * 100 }}%"></div>
                        <div class="pl-chart__bar expenses" style="height:{{ ($m['expenses'] / $maxVal) * 100 }}%"></div>
                        <div class="pl-chart__bar {{ $m['profit'] >= 0 ? 'profit-pos' : 'profit-neg' }}" style="height:{{ (abs($m['profit']) / $maxVal) * 100 }}%"></div>
                    </div>
                    <div class="pl-chart__label">{{ $m['month'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Expense Breakdown -->
    <div class="pl-panel">
        <div class="pl-panel__title">Expense Breakdown</div>

        @php
            $allExpenses = array_merge(
                $expenseByCategory,
                ['salary' => $totalSalary, 'supplies' => $totalSupplies]
            );
            $maxExpense = empty($allExpenses) || max(array_values($allExpenses)) == 0 ? 1 : max(array_values($allExpenses));
        @endphp

        <div class="pl-expense-list">
            @foreach($allExpenses as $cat => $amount)
                @if($amount > 0)
                <div class="pl-expense-row">
                    <div class="pl-expense-row__label">{{ $cat }}</div>
                    <div class="pl-expense-row__bar-bg">
                        <div class="pl-expense-row__bar" style="width:{{ ($amount / $maxExpense) * 100 }}%"></div>
                    </div>
                    <div class="pl-expense-row__value">₱{{ number_format($amount, 2) }}</div>
                </div>
                @endif
            @endforeach

            @if(empty($allExpenses) || array_sum($allExpenses) == 0)
                <div style="text-align:center;color:var(--text-3);padding:30px;font-size:13px;">
                    No expenses recorded for this period.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Detailed Breakdown Table -->
<div class="pl-panel">
    <div class="pl-panel__title">Detailed Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th style="text-align:right">Amount</th>
                <th style="text-align:right">% of Revenue</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background:rgba(59,130,246,.05)">
                <td style="font-weight:700;color:#3b82f6">Revenue (Sales)</td>
                <td style="text-align:right;font-weight:700;color:#3b82f6">₱{{ number_format($totalRevenue, 2) }}</td>
                <td style="text-align:right">100%</td>
            </tr>
            <tr><td colspan="3" style="padding:2px"></td></tr>
            @foreach($expenseByCategory as $cat => $amount)
                @if($amount > 0)
                <tr>
                    <td style="text-transform:capitalize">{{ $cat }}</td>
                    <td style="text-align:right">₱{{ number_format($amount, 2) }}</td>
                    <td style="text-align:right">{{ $totalRevenue > 0 ? number_format(($amount / $totalRevenue) * 100, 1) : '0.0' }}%</td>
                </tr>
                @endif
            @endforeach
            @if($totalSalary > 0)
            <tr>
                <td>Salary (Payslips)</td>
                <td style="text-align:right">₱{{ number_format($totalSalary, 2) }}</td>
                <td style="text-align:right">{{ $totalRevenue > 0 ? number_format(($totalSalary / $totalRevenue) * 100, 1) : '0.0' }}%</td>
            </tr>
            @endif
            @if($totalSupplies > 0)
            <tr>
                <td>Supplier Purchases</td>
                <td style="text-align:right">₱{{ number_format($totalSupplies, 2) }}</td>
                <td style="text-align:right">{{ $totalRevenue > 0 ? number_format(($totalSupplies / $totalRevenue) * 100, 1) : '0.0' }}%</td>
            </tr>
            @endif
            <tr><td colspan="3" style="padding:2px"></td></tr>
            <tr style="background:rgba(220,38,38,.04)">
                <td style="font-weight:700;color:#dc2626">Total Expenses</td>
                <td style="text-align:right;font-weight:700;color:#dc2626">₱{{ number_format($totalExpenses, 2) }}</td>
                <td style="text-align:right;font-weight:700">{{ $totalRevenue > 0 ? number_format(($totalExpenses / $totalRevenue) * 100, 1) : '0.0' }}%</td>
            </tr>
            <tr><td colspan="3" style="padding:2px"></td></tr>
            <tr style="background:{{ $netProfit >= 0 ? 'rgba(22,163,74,.06)' : 'rgba(220,38,38,.06)' }}">
                <td style="font-weight:800;font-size:14px">Net Profit</td>
                <td style="text-align:right;font-weight:800;font-size:14px;color:{{ $netProfit >= 0 ? '#16a34a' : '#dc2626' }}">
                    {{ $netProfit >= 0 ? '+' : '-' }}₱{{ number_format(abs($netProfit), 2) }}
                </td>
                <td style="text-align:right;font-weight:700">{{ number_format($profitMargin, 1) }}%</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    function setPeriod(period) {
        const url = new URL(window.location.href);
        url.searchParams.set('period', period);
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
