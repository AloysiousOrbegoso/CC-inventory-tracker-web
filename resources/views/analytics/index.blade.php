@extends('layouts.sidebar')

@section('title', 'Analytics')

@section('content')
<style>
    /* ══ ANALYTICS-SPECIFIC STYLES ════════════════════════════════════ */
    .analytics-tabs {
        display: flex;
        gap: 5px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0;
    }

    .analytics-tab {
        padding: 10px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        border-bottom: 2px solid transparent;
        background: transparent;
        color: var(--brown);
        opacity: 0.5;
        transition: all .15s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .analytics-tab:hover {
        opacity: 0.8;
    }

    .analytics-tab.active {
        opacity: 1;
        border-bottom-color: var(--terra);
    }

    .analytics-tab__icon {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
    }

    /* ══ VIEW LABEL ══════════════════════════════════════════════════ */
    .view-label {
        margin-bottom: 20px;
    }

    .view-label__btn {
        padding: 8px 20px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        cursor: default;
        border: 1.5px solid #f59e0b;
        background: #fef3c7;
        color: #92400e;
        font-family: var(--font);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* ══ ACTIVITY GRID ════════════════════════════════════════════════ */
    .activity-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 1100px) {
        .activity-grid { grid-template-columns: 1fr; }
    }

    .activity-left, .activity-right {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* ══ CARDS ════════════════════════════════════════════════════════ */
    .analytics-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .analytics-card__header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
    }

    .analytics-card__title {
        font-size: 14px;
        font-weight: 700;
    }

    .analytics-card__body {
        padding: 16px 20px;
    }

    /* ══ TRANSACTION ITEMS ════════════════════════════════════════════ */
    .transaction-item {
        padding: 12px 0;
        border-bottom: 1px solid var(--border);
    }

    .transaction-item:last-child {
        border-bottom: none;
    }

    .transaction-item__title {
        font-size: 13px;
        font-weight: 700;
        color: var(--terra);
        margin-bottom: 8px;
    }

    .transaction-item__row {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .transaction-item__total {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        font-weight: 700;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed var(--border);
    }

    /* ══ LEAKAGE ITEMS ════════════════════════════════════════════════ */
    .leakage-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }

    .leakage-item:last-child {
        border-bottom: none;
    }

    .leakage-item__amount {
        font-weight: 600;
    }

    /* ══ METRIC CARD ══════════════════════════════════════════════════ */
    .metric-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .metric-card__label {
        font-size: 14px;
        font-weight: 600;
    }

    .metric-card__value {
        font-size: 28px;
        font-weight: 800;
        color: var(--green);
    }

    /* ══ BAR CHART ════════════════════════════════════════════════════ */
    .bar-chart {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        height: 120px;
        padding-top: 10px;
    }

    .bar-col {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        height: 100%;
    }

    .bar {
        width: 100%;
        border-radius: 4px 4px 0 0;
        background: linear-gradient(to top, var(--terra-dk), var(--terra-lt));
        min-height: 2px;
        margin-top: auto;
        transition: height .3s ease;
    }

    .bar:hover {
        opacity: .8;
    }

    .bar-label {
        font-size: 9px;
        font-weight: 600;
        opacity: .5;
        white-space: nowrap;
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .chart-title {
        font-size: 14px;
        font-weight: 700;
    }

    .chart-select {
        padding: 5px 12px;
        border-radius: 6px;
        border: 1.5px solid var(--border);
        background: #fff;
        font-size: 12px;
        font-weight: 600;
        font-family: var(--font);
        cursor: pointer;
    }

    .chart-trend {
        font-size: 14px;
        font-weight: 700;
        color: var(--green);
    }

    .chart-trend .vs {
        font-size: 11px;
        font-weight: 500;
        opacity: .6;
    }

    .chart-stats {
        text-align: right;
    }

    .chart-stats__value {
        font-size: 22px;
        font-weight: 800;
    }

    .chart-stats__label {
        font-size: 11px;
        opacity: .5;
    }

    .chart-stats__trend {
        font-size: 10px;
        color: var(--green);
        font-weight: 600;
    }

    /* ══ INVENTORY TABLE ══════════════════════════════════════════════ */
    .inventory-table {
        width: 100%;
        border-collapse: collapse;
    }

    .inventory-table thead th {
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        padding: 14px 16px;
        background: var(--cream);
        border-bottom: 2px solid var(--border);
    }

    .inventory-table tbody td {
        padding: 14px 16px;
        font-size: 13px;
        border-bottom: 1px solid var(--border);
    }

    .inventory-table tbody tr:hover {
        background: rgba(180,83,83,.03);
    }

    /* ══ STATUS BADGES ════════════════════════════════════════════════ */
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }

    .status-badge.severe { background: rgba(220,38,38,.1); color: #dc2626; }
    .status-badge.moderate { background: rgba(234,179,8,.1); color: #b45309; }
    .status-badge.none, .status-badge.normal, .status-badge.stocked { 
        background: rgba(22,163,74,.1); color: #16a34a; 
    }
    .status-badge.low { background: rgba(220,38,38,.1); color: #dc2626; }

    /* ══ EMPTY STATE ══════════════════════════════════════════════════ */
    .empty-state {
        text-align: center;
        padding: 32px;
        color: var(--brown);
        opacity: .4;
        font-size: 13px;
    }
</style>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <div class="text-[22px] font-extrabold tracking-tight">Analytics</div>
        <div class="text-[13px] text-ink-2 mt-0.5">Trends, comparisons, and leakage across branches.</div>
    </div>
</div>

{{-- ═══ BUSINESS TABS (with toggle as first tab) ═══ --}}
<div class="analytics-tabs">
    <button class="analytics-tab active" id="viewToggleTab" onclick="toggleView()">
        <span class="analytics-tab__icon" style="background: #B45353; color: #fff;">I</span>
        <span id="toggleTabText">Inventory</span>
    </button>
    @foreach($branches as $branch)
        <button class="analytics-tab {{ ($selectedBranchId ?? null) == $branch->id ? 'active' : '' }}" data-business="{{ $branch->id }}" onclick="switchBusiness('{{ $branch->id }}', this)">
            {{ $branch->name }}
        </button>
    @endforeach
</div>

{{-- ═══ VIEW LABEL ═══ --}}
<div class="view-label">
    <button class="view-label__btn" id="viewLabelBtn">
        <span id="viewLabelText">Current Activity</span>
    </button>
</div>

@if($branchComparison->isNotEmpty())
{{-- ═══ BRANCH COMPARISON (super_admin only — a manager only ever sees one branch) ═══ --}}
<div class="analytics-card" style="margin-bottom: 16px;">
    <div class="analytics-card__header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div class="analytics-card__title">Branch Comparison — This Year</div>
        <a href="{{ route('analytics.export') }}" class="btn-sm">Export CSV</a>
    </div>
    <div class="analytics-card__body" style="padding: 0;">
        <table class="inventory-table">
            <thead>
                <tr><th>Branch</th><th>Revenue</th><th>Orders</th></tr>
            </thead>
            <tbody>
                @foreach($branchComparison as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>&#8369;{{ number_format($row['revenue'], 2) }}</td>
                        <td>{{ number_format($row['orders']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ═══ ACTIVITY VIEW ═══ --}}
<div id="activityView">
    <div class="activity-grid">
        {{-- Left Column --}}
        <div class="activity-left">
            {{-- Recent Transactions --}}
            <div class="analytics-card">
                <div class="analytics-card__header">
                    <div class="analytics-card__title">Recent Transaction</div>
                </div>
                <div class="analytics-card__body">
                    @forelse($recentTransactions as $index => $txn)
                        <div class="transaction-item">
                            <div class="transaction-item__title">Transaction {{ $recentTransactions->count() - $index }}</div>
                            @if($txn->product)
                                <div class="transaction-item__row">
                                    <span>1 {{ $txn->product->name }}</span>
                                    <span>&#8369;{{ number_format($txn->product->price ?? 0, 0) }}</span>
                                </div>
                            @endif
                            <div class="transaction-item__total">
                                <span>TOTAL:</span>
                                <span>&#8369;{{ number_format($txn->total_amount, 0) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-icon" style="padding:20px 16px">
                            <div style="font-size:22px;margin-bottom:6px">🧾</div>
                            <div style="font-size:12px;font-weight:600;color:var(--text)">No transactions yet</div>
                            <div style="font-size:11px;color:var(--text-3)">Transactions will appear as sales are recorded.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Current Leakage --}}
            <div class="analytics-card">
                <div class="analytics-card__header">
                    <div class="analytics-card__title">Current Leakage</div>
                </div>
                <div class="analytics-card__body">
                    @forelse($currentLeakage as $leak)
                        <div class="leakage-item">
                            <span>{{ $leak['name'] }}</span>
                            <span class="leakage-item__amount" style="color: {{ $leak['amount'] > 50 ? '#dc2626' : 'var(--brown)' }}">
                                {{ number_format($leak['amount']) }} {{ $leak['unit'] }}
                            </span>
                        </div>
                    @empty
                        <div class="empty-state-icon" style="padding:20px 16px">
                            <div style="font-size:22px;margin-bottom:6px">✅</div>
                            <div style="font-size:12px;font-weight:600;color:var(--text)">No leakage detected</div>
                            <div style="font-size:11px;color:var(--text-3)">All inventory is accounted for.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="activity-right">
            {{-- Flags Detected --}}
            <div class="analytics-card">
                <div class="analytics-card__header">
                    <div class="analytics-card__title">Flags Detected</div>
                </div>
                <div class="analytics-card__body">
                    @forelse($activeAlerts->take(3) as $alert)
                        <a href="{{ route('alerts', ['severity' => $alert->severity, 'status' => 'pending']) }}" class="leakage-item" style="text-decoration:none;color:inherit;">
                            <span>{{ $alert->ingredient->name ?? 'Unknown' }}</span>
                            <span class="status-badge {{ $alert->severity }}">{{ ucfirst($alert->severity) }}</span>
                        </a>
                    @empty
                        <div class="empty-state-icon" style="padding:20px 16px">
                            <div style="font-size:22px;margin-bottom:6px">✅</div>
                            <div style="font-size:12px;font-weight:600;color:var(--text)">No flags detected</div>
                            <div style="font-size:11px;color:var(--text-3)">All stock levels are normal.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Total Profit Margin --}}
            <div class="analytics-card">
                <div class="analytics-card__body metric-card">
                    <span class="metric-card__label">Total Profit Margin</span>
                    <span class="metric-card__value">{{ $profitMargin !== null ? $profitMargin.'%' : '—' }} {!! $profitMargin > 0 ? '&#8593;' : '' !!}</span>
                </div>
            </div>

            {{-- Performance Analytics --}}
            <div class="analytics-card">
                <div class="analytics-card__header">
                    <div class="analytics-card__title">Performance Analytics</div>
                </div>
                <div class="analytics-card__body">
                    <div class="chart-header">
                        <select class="chart-select" onchange="updatePerformanceChart(this.value)">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                        </select>
                        <div>
                            <span class="chart-trend">{{ $performanceTrend }}% {!! $performanceTrend >= 0 ? '&#8593;' : '&#8595;' !!}</span>
                            <div class="chart-trend vs">vs last month</div>
                        </div>
                    </div>
                    <div class="bar-chart" id="performanceChart">
                    @php
                        $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        $currentMonth = now()->month;
                    @endphp
                    @for ($m = 1; $m <= 12; $m++)
                        @php
                            $val = $monthlySales[$m] ?? 0;
                            $maxVal = max(array_values($monthlySales ?: [1]));
                            $h = $maxVal > 0 ? max(3, ($val / $maxVal) * 100) : 3;
                            $isFuture = $m > $currentMonth;
                        @endphp
                        <div class="bar-col">
                            <div class="bar" style="height: {{ $isFuture ? 0 : $h }}%; {{ $isFuture ? 'opacity:.15' : '' }}"></div>
                            <div class="bar-label">{{ $monthLabels[$m - 1] }}</div>
                        </div>
                    @endfor
                    </div>
                </div>
            </div>

            {{-- Historical Trends --}}
            <div class="analytics-card">
                <div class="analytics-card__header">
                    <div class="analytics-card__title">Historical Trends</div>
                </div>
                <div class="analytics-card__body">
                    <div class="chart-header">
                        <select class="chart-select">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                        </select>
                        <div class="chart-stats">
                            <div class="chart-stats__value">{{ number_format($totalOrders) }}</div>
                            <div class="chart-stats__label">Orders</div>
                            <div class="chart-stats__trend">{{ $orderTrend >= 0 ? $orderTrend.'% increase' : abs($orderTrend).'% decrease' }}</div>
                        </div>
                    </div>
                    <div class="bar-chart">
                        @for ($m = 1; $m <= 12; $m++)@php
                            $val = $historicalData[$m] ?? 0;
                            $maxVal = max(array_values($historicalData ?: [1]));
                            $h = $maxVal > 0 ? max(3, ($val / $maxVal) * 100) : 3;
                            $isFuture = $m > $currentMonth;
                        @endphp
                        <div class="bar-col">
                            <div class="bar" style="height: {{ $isFuture ? 0 : $h }}%; {{ $isFuture ? 'opacity:.15' : '' }}"></div>
                            <div class="bar-label">{{ $monthLabels[$m - 1] }}</div>
                        </div>
                    @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ INVENTORY VIEW ═══ --}}
<div id="inventoryView" style="display: none;">
    <div class="analytics-card">
        <div class="analytics-card__body" style="padding: 0;">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Items</th>
                        <th>Estimated Amount</th>
                        <th>On-site Amount</th>
                        <th>Leakage Indicator</th>
                        <th>Inventory Indicator</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventoryItems as $item)
                        @php
                            $leakage = $item->estimated_amount - $item->on_site_amount;
                            $leakagePct = $item->estimated_amount > 0 ? ($leakage / $item->estimated_amount) * 100 : 0;
                            $leakageStatus = 'none';
                            if ($leakagePct > 10) $leakageStatus = 'severe';
                            elseif ($leakagePct > 5) $leakageStatus = 'moderate';

                            $inventoryStatus = 'stocked';
                            if ($item->on_site_amount <= 0) $inventoryStatus = 'out of stock';
                            elseif ($item->on_site_amount < ($item->min_threshold ?? 0)) $inventoryStatus = 'low';

                            $remark = 'normal';
                            if ($leakageStatus === 'severe') $remark = 'low';
                            elseif ($leakageStatus === 'moderate') $remark = 'moderate';
                        @endphp
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ number_format($item->estimated_amount, 0) }} {{ $item->unit }}</td>
                            <td>{{ number_format($item->on_site_amount, 0) }} {{ $item->unit }}</td>
                            <td>
                                <span class="status-badge {{ $leakageStatus }}">{{ $leakageStatus }}</span>
                            </td>
                            <td>
                                <span class="status-badge {{ $inventoryStatus === 'stocked' ? 'stocked' : ($inventoryStatus === 'low' ? 'low' : 'severe') }}">{{ $inventoryStatus }}</span>
                            </td>
                            <td>
                                <span class="status-badge {{ $remark }}">{{ $remark }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:24px;">
                            <div style="font-size:22px;margin-bottom:6px">📦</div>
                            <div style="font-size:12px;font-weight:600;color:var(--text)">No inventory items yet</div>
                            <div style="font-size:11px;color:var(--text-3)">Add ingredients and stock to see inventory data here.</div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // ══ CURRENT VIEW STATE ═══════════════════════════════════════════════
    let currentView = 'activity'; // 'activity' shows Activity content, tab shows 'Inventory'

    // ══ VIEW TOGGLE (Switch between Activity & Inventory) ═══════════════
    function toggleView() {
        const tab = document.getElementById('viewToggleTab');
        const tabText = document.getElementById('toggleTabText');
        const tabIcon = tab.querySelector('.analytics-tab__icon');
        const labelText = document.getElementById('viewLabelText');
        
        if (currentView === 'activity') {
            // Switch to Inventory view
            currentView = 'inventory';
            tabText.textContent = 'Activity';
            tabIcon.textContent = 'A';
            labelText.textContent = 'Real-Time Inventory Tracking';
            document.getElementById('activityView').style.display = 'none';
            document.getElementById('inventoryView').style.display = 'block';
        } else {
            // Switch to Activity view
            currentView = 'activity';
            tabText.textContent = 'Inventory';
            tabIcon.textContent = 'I';
            labelText.textContent = 'Current Activity';
            document.getElementById('activityView').style.display = 'block';
            document.getElementById('inventoryView').style.display = 'none';
        }
    }

    // ══ BUSINESS SWITCHING ══════════════════════════════════════════════
    function switchBusiness(business, el) {
        // Update URL without reload
        var url = new URL(window.location.href);
        url.searchParams.set('branch_id', business);
        history.pushState({}, '', url.toString());

        // Update active tab styling
        document.querySelectorAll('.analytics-tab').forEach(function(t) {
            if (t.id !== 'viewToggleTab') {
                t.classList.remove('active');
            }
        });
        el.classList.add('active');

        // Show loading state
        document.querySelectorAll('.analytics-card__body').forEach(function(body) {
            body.style.opacity = '0.4';
        });

        // Fetch new data via AJAX
        fetch('/ajax/analytics?branch_id=' + business, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            renderAnalyticsData(data);
            document.querySelectorAll('.analytics-card__body').forEach(function(body) {
                body.style.opacity = '1';
            });
        })
        .catch(function() {
            document.querySelectorAll('.analytics-card__body').forEach(function(body) {
                body.style.opacity = '1';
            });
        });
    }

    function renderAnalyticsData(data) {
        var monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var currentMonth = {{ now()->month }};

        // Render recent transactions
        var txContainer = document.querySelector('.activity-left .analytics-card:first-child .analytics-card__body');
        if (txContainer && data.recentTransactions) {
            var txHtml = '';
            data.recentTransactions.forEach(function(tx, i) {
                txHtml += '<div class="transaction-item">';
                txHtml += '<div class="transaction-item__title">Transaction ' + (data.recentTransactions.length - i) + '</div>';
                if (tx.product) {
                    txHtml += '<div class="transaction-item__row"><span>1 ' + tx.product.name + '</span><span>&#8369;' + Number(tx.product.price || 0).toLocaleString() + '</span></div>';
                }
                txHtml += '<div class="transaction-item__total"><span>TOTAL:</span><span>&#8369;' + Number(tx.total_amount).toLocaleString() + '</span></div>';
                txHtml += '</div>';
            });
            txContainer.innerHTML = txHtml || '<div class="empty-state-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/><line x1="9" y1="11" x2="15" y2="11"/></svg><span class="empty-state-text">No transactions recorded yet.</span></div>';
        }

        // Render current leakage
        var leakContainer = document.querySelectorAll('.activity-left .analytics-card')[1];
        if (leakContainer && data.currentLeakage) {
            var leakBody = leakContainer.querySelector('.analytics-card__body');
            var leakHtml = '';
            data.currentLeakage.forEach(function(leak) {
                leakHtml += '<div class="leakage-item"><span>' + leak.name + '</span><span class="leakage-item__amount" style="color:' + (leak.amount > 50 ? '#dc2626' : 'var(--brown)') + '">' + Number(leak.amount).toLocaleString() + ' ' + leak.unit + '</span></div>';
            });
            leakBody.innerHTML = leakHtml || '<div class="empty-state-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c4.97 0 9-3.582 9-8 0-4.418-4.03-11-9-13-4.97 2-9 8.582-9 13 0 4.418 4.03 8 9 8z"/></svg><span class="empty-state-text">No leakage data.</span></div>';
        }

        // Render flags detected
        var flagContainer = document.querySelector('.activity-right .analytics-card:first-child .analytics-card__body');
        if (flagContainer && data.activeAlerts) {
            var flagHtml = '';
            data.activeAlerts.slice(0, 3).forEach(function(alert) {
                var alertsUrl = '{{ route('alerts') }}?severity=' + encodeURIComponent(alert.severity) + '&status=pending';
                flagHtml += '<a href="' + alertsUrl + '" class="leakage-item" style="text-decoration:none;color:inherit;"><span>' + (alert.ingredient ? alert.ingredient.name : 'Unknown') + '</span><span class="status-badge ' + alert.severity + '">' + (alert.severity ? alert.severity.charAt(0).toUpperCase() + alert.severity.slice(1) : '') + '</span></a>';
            });
            flagContainer.innerHTML = flagHtml || '<div class="empty-state-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg><span class="empty-state-text">No flags detected.</span></div>';
        }

        // Render profit margin
        var profitEl = document.querySelector('.metric-card__value');
        if (profitEl) {
            profitEl.innerHTML = data.profitMargin !== null ? (data.profitMargin + '% ' + (data.profitMargin > 0 ? '&#8593;' : '')) : '&mdash;';
        }

        // Render performance chart
        var perfChart = document.getElementById('performanceChart');
        if (perfChart && data.monthlySales) {
            var maxVal = Math.max.apply(null, Object.values(data.monthlySales).concat([1]));
            var chartHtml = '';
            for (var m = 1; m <= 12; m++) {
                var val = data.monthlySales[m] || 0;
                var h = maxVal > 0 ? Math.max(3, (val / maxVal) * 100) : 3;
                var isFuture = m > currentMonth;
                chartHtml += '<div class="bar-col"><div class="bar" style="height:' + (isFuture ? 0 : h) + '%;' + (isFuture ? 'opacity:.15' : '') + '"></div><div class="bar-label">' + monthLabels[m-1] + '</div></div>';
            }
            perfChart.innerHTML = chartHtml;
        }

        // Render trend
        var trendEl = document.querySelector('.chart-trend');
        if (trendEl) {
            trendEl.innerHTML = data.performanceTrend + '% ' + (data.performanceTrend >= 0 ? '&#8593;' : '&#8595;');
        }

        // Render historical chart
        var histCharts = document.querySelectorAll('.bar-chart');
        if (histCharts.length > 1 && data.historicalData) {
            var histMax = Math.max.apply(null, Object.values(data.historicalData).concat([1]));
            var histHtml = '';
            for (var m2 = 1; m2 <= 12; m2++) {
                var hVal = data.historicalData[m2] || 0;
                var hH = histMax > 0 ? Math.max(3, (hVal / histMax) * 100) : 3;
                var hFuture = m2 > currentMonth;
                histHtml += '<div class="bar-col"><div class="bar" style="height:' + (hFuture ? 0 : hH) + '%;' + (hFuture ? 'opacity:.15' : '') + '"></div><div class="bar-label">' + monthLabels[m2-1] + '</div></div>';
            }
            histCharts[1].innerHTML = histHtml;
        }

        // Render historical stats
        var histStats = document.querySelector('.chart-stats__value');
        if (histStats) {
            histStats.textContent = Number(data.totalOrders).toLocaleString();
        }
        var histTrend = document.querySelector('.chart-stats__trend');
        if (histTrend) {
            histTrend.textContent = data.orderTrend >= 0 ? (data.orderTrend + '% increase') : (Math.abs(data.orderTrend) + '% decrease');
        }

        // Render inventory table
        var invBody = document.querySelector('#inventoryView tbody');
        if (invBody && data.inventoryItems) {
            var invHtml = '';
            data.inventoryItems.forEach(function(item) {
                var estimatedAmount = Number(item.estimated_amount);
                var onSiteAmount = Number(item.on_site_amount);
                var minThreshold = Number(item.min_threshold || 0);
                var leakage = estimatedAmount - onSiteAmount;
                var leakagePct = estimatedAmount > 0 ? (leakage / estimatedAmount) * 100 : 0;
                var leakageStatus = 'none';
                if (leakagePct > 10) leakageStatus = 'severe';
                else if (leakagePct > 5) leakageStatus = 'moderate';
                var inventoryStatus = 'stocked';
                if (onSiteAmount <= 0) inventoryStatus = 'out of stock';
                else if (onSiteAmount < minThreshold) inventoryStatus = 'low';
                var remark = 'normal';
                if (leakageStatus === 'severe') remark = 'low';
                else if (leakageStatus === 'moderate') remark = 'moderate';
                invHtml += '<tr><td>' + item.item_name + '</td><td>' + estimatedAmount.toLocaleString() + ' ' + item.unit + '</td><td>' + onSiteAmount.toLocaleString() + ' ' + item.unit + '</td><td><span class="status-badge ' + leakageStatus + '">' + leakageStatus + '</span></td><td><span class="status-badge ' + (inventoryStatus === 'stocked' ? 'stocked' : (inventoryStatus === 'low' ? 'low' : 'severe')) + '">' + inventoryStatus + '</span></td><td><span class="status-badge ' + remark + '">' + remark + '</span></td></tr>';
            });
            invBody.innerHTML = invHtml || '<tr><td colspan="6" style="text-align:center;opacity:.4;padding:24px;">No inventory items found.</td></tr>';
        }
    }

    // ══ CHART UPDATE ════════════════════════════════════════════════════
    function updatePerformanceChart(period) {
        console.log('Updating chart for period:', period);
    }
</script>
@endsection
