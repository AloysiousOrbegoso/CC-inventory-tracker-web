@extends('layouts.sidebar')

@section('title', 'Discrepancy Alerts')
@section('subtitle', 'Stock mismatches and shift variances requiring review')

@section('content')

    @php
        $severityBadges = ['high' => 'red', 'medium' => 'amber', 'low' => 'blue'];
        $statusBadges = ['pending' => 'amber', 'reviewed' => 'green', 'dismissed' => 'gray'];
        $peso = fn ($n) => '₱' . number_format((float) $n, 2);
    @endphp

    {{-- KPI Row --}}
    <div class="grid grid-cols-4 max-[880px]:grid-cols-2 card border-[1.5px] border-line overflow-hidden divide-x divide-line max-[880px]:divide-x-0 mb-5">
        <div class="p-4 text-center max-[880px]:border-b max-[880px]:border-line">
            <div class="text-[26px] font-extrabold text-accent leading-none">{{ $kpi_active }}</div>
            <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Active Flags</div>
        </div>
        <div class="p-4 text-center max-[880px]:border-b max-[880px]:border-line">
            <div class="text-[26px] font-extrabold text-green-600 leading-none">{{ $kpi_resolved_this_month }}</div>
            <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Resolved This Month</div>
        </div>
        <div class="p-4 text-center">
            <div class="text-[26px] font-extrabold {{ $kpi_high_severity > 0 ? 'text-red-600' : 'text-accent' }} leading-none">{{ $kpi_high_severity }}</div>
            <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">High Severity</div>
        </div>
        <div class="p-4 text-center">
            <div class="text-[26px] font-extrabold text-green-600 leading-none">{{ $peso($kpi_value_recovered) }}</div>
            <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Value Recovered</div>
        </div>
    </div>

    <div class="filter-tabs status-tabs">
        <button type="button" class="tab active" data-status="all">All</button>
        <button type="button" class="tab" data-status="pending">Pending</button>
        <button type="button" class="tab" data-status="reviewed">Reviewed</button>
        <button type="button" class="tab" data-status="dismissed">Dismissed</button>
    </div>

    <div class="filter-tabs" id="date-tabs">
        <button type="button" class="tab active" data-range="all">All Time</button>
        <button type="button" class="tab" data-range="today">Today</button>
        <button type="button" class="tab" data-range="week">This Week</button>
        <button type="button" class="tab" data-range="month">This Month</button>
        <button type="button" class="tab" data-range="quarter">This Quarter</button>
        <button type="button" class="tab" data-range="custom">Custom</button>
    </div>

    <div class="toolbar" id="custom-date-row" style="display:none">
        <input type="date" id="custom-date-from" class="select-filter" style="width:auto">
        <span class="text-xs opacity-50">to</span>
        <input type="date" id="custom-date-to" class="select-filter" style="width:auto">
        <button type="button" class="btn-pill" id="custom-date-apply">Apply</button>
    </div>

    <div class="toolbar">
        <select id="severity-filter" class="select-filter">
            <option value="all">All Severities ({{ $alerts->count() }})</option>
            <option value="high">High ({{ $severity_counts['high'] ?? 0 }})</option>
            <option value="medium">Medium ({{ $severity_counts['medium'] ?? 0 }})</option>
            <option value="low">Low ({{ $severity_counts['low'] ?? 0 }})</option>
        </select>
        <div></div>
    </div>

    @if ($alerts->isEmpty())
        <div class="card-panel">
            <div class="all-clear" style="padding:24px">
                <div style="font-size:28px;margin-bottom:8px">✅</div>
                <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">All clear!</div>
                <div style="font-size:12px;color:var(--text-2)">No discrepancy alerts. All stock levels are normal.</div>
            </div>
        </div>
    @else
        <div class="card-panel">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Ingredient</th>
                        <th>Expected</th>
                        <th>Actual</th>
                        <th>Variance</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="alerts-tbody">
                    @foreach ($alerts as $alert)
                        <tr data-status="{{ $alert->status }}" data-severity="{{ $alert->severity }}" data-created="{{ $alert->created_at->timestamp }}">
                            <td class="cell-primary">{{ $alert->branch->name ?? '—' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $alert->type)) }}</td>
                            <td><span class="badge {{ $severityBadges[$alert->severity] ?? 'gray' }}">{{ ucfirst($alert->severity) }}</span></td>
                            <td>{{ $alert->ingredient->name ?? '—' }}</td>
                            <td>{{ $alert->expected_value !== null ? number_format($alert->expected_value, 2) : '—' }}</td>
                            <td>{{ $alert->actual_value !== null ? number_format($alert->actual_value, 2) : '—' }}</td>
                            <td class="variance-cell">{{ $alert->variance !== null ? '−' . number_format(abs($alert->variance), 2) : '—' }}</td>
                            <td><span class="badge {{ $statusBadges[$alert->status] ?? 'gray' }}">{{ ucfirst($alert->status) }}</span></td>
                            <td>{{ $alert->created_at->format('M d, Y g:iA') }}</td>
                            <td>
                                @if ($alert->status === 'pending')
                                    <button type="button" class="btn-pill">Review</button>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <script>
        (function () {
            const statusTabs = document.querySelectorAll('.status-tabs .tab');
            const dateTabs = document.querySelectorAll('#date-tabs .tab');
            const severitySelect = document.getElementById('severity-filter');
            const rows = document.querySelectorAll('#alerts-tbody tr[data-status]');
            const customRow = document.getElementById('custom-date-row');
            const customFrom = document.getElementById('custom-date-from');
            const customTo = document.getElementById('custom-date-to');
            let activeStatus = 'all';
            let activeRange = 'all';

            function rangeStart(range) {
                const now = new Date();
                if (range === 'today') return new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() / 1000;
                if (range === 'week') { const d = new Date(now); d.setDate(d.getDate() - d.getDay()); d.setHours(0, 0, 0, 0); return d.getTime() / 1000; }
                if (range === 'month') return new Date(now.getFullYear(), now.getMonth(), 1).getTime() / 1000;
                if (range === 'quarter') return new Date(now.getFullYear(), Math.floor(now.getMonth() / 3) * 3, 1).getTime() / 1000;
                return null;
            }

            function applyFilters() {
                const severity = severitySelect?.value || 'all';
                let customFromTs = null, customToTs = null;
                if (activeRange === 'custom') {
                    customFromTs = customFrom.value ? new Date(customFrom.value).getTime() / 1000 : null;
                    customToTs = customTo.value ? (new Date(customTo.value).getTime() / 1000) + 86400 : null;
                }
                const start = activeRange === 'custom' ? null : rangeStart(activeRange);

                rows.forEach(function (row) {
                    const matchesStatus = activeStatus === 'all' || row.dataset.status === activeStatus;
                    const matchesSeverity = severity === 'all' || row.dataset.severity === severity;
                    const created = parseInt(row.dataset.created, 10);
                    let matchesDate = true;
                    if (activeRange === 'custom') {
                        matchesDate = (customFromTs === null || created >= customFromTs) && (customToTs === null || created <= customToTs);
                    } else if (start !== null) {
                        matchesDate = created >= start;
                    }
                    row.style.display = (matchesStatus && matchesSeverity && matchesDate) ? '' : 'none';
                });
            }

            statusTabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    statusTabs.forEach(function (t) { t.classList.remove('active'); });
                    tab.classList.add('active');
                    activeStatus = tab.dataset.status;
                    applyFilters();
                });
            });

            dateTabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    dateTabs.forEach(function (t) { t.classList.remove('active'); });
                    tab.classList.add('active');
                    activeRange = tab.dataset.range;
                    customRow.style.display = activeRange === 'custom' ? 'flex' : 'none';
                    if (activeRange !== 'custom') applyFilters();
                });
            });

            document.getElementById('custom-date-apply')?.addEventListener('click', applyFilters);

            severitySelect?.addEventListener('change', applyFilters);

            // Pre-apply filters passed via the URL (e.g. a Flags Detected link from Analytics).
            const params = new URLSearchParams(window.location.search);
            const urlSeverity = params.get('severity');
            const urlStatus = params.get('status');
            if (urlSeverity && severitySelect) {
                severitySelect.value = urlSeverity;
            }
            if (urlStatus) {
                const matchingTab = Array.from(statusTabs).find(function (t) { return t.dataset.status === urlStatus; });
                if (matchingTab) {
                    statusTabs.forEach(function (t) { t.classList.remove('active'); });
                    matchingTab.classList.add('active');
                    activeStatus = urlStatus;
                }
            }
            if (urlSeverity || urlStatus) {
                applyFilters();
            }
        })();
    </script>

@endsection
