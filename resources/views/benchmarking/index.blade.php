@extends('layouts.sidebar')

@section('title', 'Branch Benchmarking')

@section('styles')
    .bm-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .bm-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 20px;
        text-align: center;
    }
    .bm-card__label { font-size: 12px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px; }
    .bm-card__value { font-size: 24px; font-weight: 800; color: var(--text); }

    .bm-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .bm-panel__title { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 16px; }

    .bm-filters {
        display: flex; gap: 8px; margin-bottom: 24px; align-items: center;
    }
    .bm-filter-btn {
        padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
        border: 1.5px solid var(--border); background: #fff; color: var(--text); cursor: pointer;
    }
    .bm-filter-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }

    .rank-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; border-radius: 50%; font-size: 12px; font-weight: 700;
    }
    .rank-badge.gold { background: #fef08a; color: #854d0e; }
    .rank-badge.silver { background: #e5e7eb; color: #374151; }
    .rank-badge.bronze { background: #fed7aa; color: #9a3412; }
    .rank-badge.other { background: #f3f4f6; color: #6b7280; }
@endsection

@section('content')
<div class="bm-filters">
    @foreach([1 => '1 Month', 3 => '3 Months', 6 => '6 Months'] as $m => $label)
        <button class="bm-filter-btn {{ $months == $m ? 'active' : '' }}" onclick="setMonths({{ $m }})">{{ $label }}</button>
    @endforeach
</div>

<!-- Company Summary -->
<div class="bm-summary">
    <div class="bm-card">
        <div class="bm-card__label">Company Revenue</div>
        <div class="bm-card__value">₱{{ number_format($companyTotals['revenue'], 0) }}</div>
    </div>
    <div class="bm-card">
        <div class="bm-card__label">Company Expenses</div>
        <div class="bm-card__value" style="color:#dc2626">₱{{ number_format($companyTotals['expenses'], 0) }}</div>
    </div>
    <div class="bm-card">
        <div class="bm-card__label">Company Profit</div>
        <div class="bm-card__value" style="color:{{ $companyTotals['profit'] >= 0 ? '#16a34a' : '#dc2626' }}">
            ₱{{ number_format($companyTotals['profit'], 0) }}
        </div>
    </div>
    <div class="bm-card">
        <div class="bm-card__label">Avg Margin</div>
        <div class="bm-card__value">{{ $companyTotals['profit_margin'] }}%</div>
    </div>
</div>

<!-- Rankings -->
<div class="bm-panel">
    <div class="bm-panel__title">🏆 Profitability Rankings</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px">Rank</th>
                <th>Branch</th>
                <th style="text-align:right">Revenue</th>
                <th style="text-align:right">Expenses</th>
                <th style="text-align:right">Profit</th>
                <th style="text-align:right">Margin</th>
                <th style="text-align:right">Staff</th>
                <th style="text-align:right">Revenue/Employee</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rankings as $i => $b)
                <tr style="{{ $i === 0 ? 'background:rgba(234,179,8,.05)' : '' }}">
                    <td>
                        <span class="rank-badge {{ $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : 'other')) }}">
                            {{ $i + 1 }}
                        </span>
                    </td>
                    <td style="font-weight:700">{{ $b['name'] }}</td>
                    <td style="text-align:right;color:#3b82f6;font-weight:600">₱{{ number_format($b['revenue'], 2) }}</td>
                    <td style="text-align:right;color:#f97316;font-weight:600">₱{{ number_format($b['expenses'], 2) }}</td>
                    <td style="text-align:right;font-weight:700;color:{{ $b['profit'] >= 0 ? '#16a34a' : '#dc2626' }}">
                        ₱{{ number_format($b['profit'], 2) }}
                    </td>
                    <td style="text-align:right;font-weight:600">{{ $b['profit_margin'] }}%</td>
                    <td style="text-align:right">{{ $b['staff_count'] }}</td>
                    <td style="text-align:right;font-weight:600">₱{{ number_format($b['revenue_per_employee'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Visual Comparison -->
<div class="bm-panel">
    <div class="bm-panel__title">📊 Revenue Comparison</div>
    @php $maxRev = max(1, ...$branchStats->pluck('revenue')->toArray()); @endphp
    @foreach($branchStats as $b)
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
            <span style="width:120px;font-size:12px;font-weight:600;color:var(--text)">{{ $b['name'] }}</span>
            <div style="flex:1;height:20px;background:var(--bg);border-radius:6px;overflow:hidden">
                <div style="height:100%;width:{{ ($b['revenue'] / $maxRev) * 100 }}%;background:#3b82f6;border-radius:6px;transition:width .4s"></div>
            </div>
            <span style="width:100px;text-align:right;font-size:12px;font-weight:600">₱{{ number_format($b['revenue'], 0) }}</span>
        </div>
    @endforeach
</div>

<script>
    function setMonths(m) { const u = new URL(window.location.href); u.searchParams.set('months', m); window.location.href = u.toString(); }
</script>
@endsection
