@php
    $currentBusinessTab = $currentBusinessTab ?? 'summary';
    $selectedBranchId = $selectedBranchId ?? null;
    $selectedBranch = $selectedBranchId ? $branches->firstWhere('id', $selectedBranchId) : $branches->first();
@endphp

<style>
    .biz-header-bar {
        position: sticky; top: 0; z-index: 40;
        background: rgba(255,255,255,.95); backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--border);
        padding: 14px 0; margin-bottom: 16px;
    }
    .biz-header-bar__inner {
        display: flex; align-items: center; justify-content: space-between;
    }
    .biz-header-bar__left {
        display: flex; align-items: center; gap: 20px;
    }
    .biz-header-bar__title {
        font-size: 20px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .02em; margin: 0; padding: 0; line-height: 1.2;
    }
    .biz-header-bar__sep { font-weight: 400; opacity: .5; margin: 0 4px; }
    .biz-header-bar__dots-wrap { display: flex; flex-direction: column; gap: 4px; }
    .biz-header-bar__dots { display: flex; gap: 6px; }
    .biz-header-bar__branch-info {
        display: flex; align-items: center; gap: 6px; font-size: 11px; opacity: .55;
    }
    .biz-header-bar__branch-name { font-weight: 600; }
    .biz-header-bar__branch-loc { font-weight: 400; }
    .biz-header-bar__branch-loc::before { content: '\2014'; margin-right: 6px; opacity: .5; }
    .biz-header-bar__tabs { display: flex; gap: 6px; }
    .branch-dot-sm {
        width: 28px; height: 28px; border-radius: 50%;
        background: rgba(253,245,214,.9); border: 1.5px solid rgba(92,45,27,.25);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 9px; font-weight: 700; color: var(--text);
        cursor: pointer; transition: all .12s ease; text-decoration: none;
    }
    .branch-dot-sm.is-active { background: var(--accent); color: #fff; border-color: var(--accent); }
    .branch-dot-sm:hover { transform: scale(1.1); background: var(--accent); color: #fff; }
    .sub-tab {
        display: flex; align-items: center; gap: 6px;
        padding: 7px 16px; border-radius: 999px; font-size: 12px; font-weight: 600;
        color: var(--text); border: 1.5px solid var(--border); background: #fff;
        text-decoration: none; transition: all .15s ease; white-space: nowrap;
    }
    .sub-tab:hover { border-color: var(--accent); color: var(--accent); }
    .sub-tab.is-active { background: var(--accent); color: #fff; border-color: var(--accent); }
    .sub-tab.is-active svg { stroke: #fff; }
    @media (max-width: 900px) {
        .biz-header-bar__inner { flex-wrap: wrap; gap: 12px; }
        .biz-header-bar__title { font-size: 16px; }
    }
</style>

<div class="biz-header-bar">
    <div class="biz-header-bar__inner">
        <div class="biz-header-bar__left">
            <h1 class="biz-header-bar__title">Businesses <span class="biz-header-bar__sep">|</span> Owner</h1>
            <div class="biz-header-bar__dots-wrap">
                <div class="biz-header-bar__dots">
                    @foreach ($branches as $branch)
                        @php
                            $loc = $branch->location ?? $branch->name;
                            $city = trim(explode(',', $loc)[0]);
                            $ini = collect(explode(' ', $city))->map(fn($w) => strtoupper($w[0]))->take(2)->implode('');
                        @endphp
                        <a href="#" class="branch-dot-sm {{ ($selectedBranchId ?? null) == $branch->id ? 'is-active' : '' }}"
                           onclick="switchBranchDot({{ $branch->id }});return false;"
                           title="{{ $branch->name }} — {{ $branch->location }}">{{ $ini }}</a>
                    @endforeach
                </div>
                <div class="biz-header-bar__branch-info" id="branch-info">
                    <span class="biz-header-bar__branch-name">{{ $selectedBranch->name }}</span>
                    @if (!empty($selectedBranch?->location))
                        <span class="biz-header-bar__branch-loc">{{ $selectedBranch->location }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="biz-header-bar__tabs">
            <a href="{{ url('/business/summary') }}" class="sub-tab {{ $currentBusinessTab === 'summary' ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Summary
            </a>
            <a href="{{ url('/business/recipes') }}" class="sub-tab {{ $currentBusinessTab === 'recipes' ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Recipe
            </a>
            <a href="{{ url('/business/workers') }}" class="sub-tab {{ $currentBusinessTab === 'workers' ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Staff
            </a>
        </div>
    </div>
</div>

<script>
function switchBranchDot(branchId) {
    var url = new URL(window.location.href);
    url.searchParams.set('branch_id', branchId);
    history.pushState({}, '', url.toString());

    // Update active dot styling
    document.querySelectorAll('.branch-dot-sm').forEach(function(dot) {
        dot.classList.remove('is-active');
    });
    if (typeof event !== 'undefined' && event.target) {
        event.target.closest('.branch-dot-sm').classList.add('is-active');
    }

    // Fetch new summary data via AJAX
    fetch('/ajax/summary?branch_id=' + branchId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        renderSummaryData(data);
    })
    .catch(function() {});
}

function renderSummaryData(data) {
    // Update branch info
    var branchInfo = document.getElementById('branch-info');
    if (branchInfo && data.activeBranch) {
        var html = '<span class="biz-header-bar__branch-name">' + data.activeBranch.name + '</span>';
        if (data.activeBranch.location) {
            html += '<span class="biz-header-bar__branch-loc">' + data.activeBranch.location + '</span>';
        }
        branchInfo.innerHTML = html;
    }

    // Update transactions
    var txList = document.querySelector('.activity-log');
    if (txList && data.recentTransactions) {
        if (data.recentTransactions.length === 0) {
            txList.innerHTML = '<li style="font-size:13px;opacity:.4;padding:8px 0">No transactions recorded yet.</li>';
        } else {
            var txHtml = '';
            data.recentTransactions.forEach(function(tx) {
                txHtml += '<li><span><strong>Txn #' + tx.id + '</strong></span><span>&#8369;' + Number(tx.total_amount).toLocaleString(undefined, {minimumFractionDigits:2}) + '</span></li>';
                txHtml += '<li class="items-list"><span>' + (tx.product ? tx.product.name : 'Unknown Item') + ' — ' + new Date(tx.created_at).toLocaleString() + ' · ' + (tx.user ? tx.user.name : '—') + '</span></li>';
            });
            var total = data.recentTransactions.reduce(function(s, tx) { return s + parseFloat(tx.total_amount); }, 0);
            txHtml += '<li class="total-row"><span>TOTAL:</span><span>&#8369;' + total.toLocaleString(undefined, {minimumFractionDigits:2}) + '</span></li>';
            txList.innerHTML = txHtml;
        }
    }

    // Update revenue
    var profitEl = document.querySelector('.profit-value');
    if (profitEl) {
        var rev = data.totalRevenue;
        var display = rev >= 1000000 ? (rev/1000000).toFixed(2) + 'M' : (rev >= 1000 ? (rev/1000).toFixed(1) + 'k' : rev.toLocaleString(undefined, {minimumFractionDigits:2}));
        profitEl.innerHTML = '&#8369;' + display + (rev > 0 ? '<span class="arrow">&uarr;</span>' : '');
    }

    // Update chart
    if (data.monthlySales) {
        var maxSales = Math.max.apply(null, Object.values(data.monthlySales).concat([1]));
        var svgW = 300, svgH = 110, pad = 10;
        var pts = [];
        for (var m = 1; m <= 12; m++) {
            var x = pad + (m - 1) * ((svgW - pad * 2) / 11);
            var val = data.monthlySales[m] || 0;
            var y = svgH - pad - (val / maxSales) * (svgH - pad * 2);
            pts.push(x + ',' + y);
        }
        var ptsStr = pts.join(' ');
        var polyClose = ptsStr + ' ' + svgW + ',' + svgH + ' 0,' + svgH;
        var svgEl = document.querySelector('.chart-svg');
        if (svgEl) {
            svgEl.innerHTML = '<polygon points="' + polyClose + '" fill="rgba(188,97,75,.08)"/><polyline points="' + ptsStr + '" fill="none" stroke="#BC614B" stroke-width="2.5" stroke-linejoin="round"/>';
        }
    }

    // Update leakage
    var cards = document.querySelectorAll('.card');
    var leakContainer = cards.length > 1 ? cards[1] : null;
    if (leakContainer && data.leakageRows) {
        var leakHtml = '';
        if (data.leakageRows.length === 0) {
            leakHtml = '<p style="font-size:13px;opacity:.4;padding:8px 0">No leakage records found.</p>';
        } else {
            data.leakageRows.forEach(function(row) {
                leakHtml += '<div class="leakage-row"><span>' + (row.ingredient ? row.ingredient.name : 'Unknown') + '</span><span class="qty red">' + Number(row.variance).toLocaleString(undefined, {minimumFractionDigits:2}) + ' ' + (row.ingredient ? row.ingredient.unit : '') + '</span></div>';
            });
        }
        leakContainer.innerHTML = '<h3>Leakage Log (Negative Variance)</h3>' + leakHtml;
    }
}
</script>
