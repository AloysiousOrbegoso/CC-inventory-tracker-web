@extends('layouts.sidebar')

@section('title', 'Dashboard')

@section('content')
    @php
        $peso = fn($n) => '₱' . number_format((float) $n, 2);

        // ── Flag summary: worst pending severity per branch ─────────────────
        $sevRank = ['low' => 1, 'medium' => 2, 'high' => 3];
        $sevTone = ['high' => 'high', 'medium' => 'med', 'low' => 'low'];
        $flagged = [];
        foreach ($recent_flags as $f) {
            $name = $f->branch->name ?? 'Unassigned';
            $sev = $f->severity ?? 'low';
            if (!isset($flagged[$name]) || ($sevRank[$sev] ?? 0) > ($sevRank[$flagged[$name]] ?? 0)) {
                $flagged[$name] = $sev;
            }
        }

        // ── Rankings ────────────────────────────────────────────────────────
        $earners = $top_earners->filter(fn($b) => (float) ($b->revenue ?? 0) > 0)->values();
        $leakRows = collect($least_leakage);

        // ── KPI chart ───────────────────────────────────────────────────────
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $series = collect($monthly_revenue)->values();
        $peak = max((float) $series->max(), 1);
        $bestIx = $series->search($series->max());

        // ── Employee Status (shared by the stat card and the branch chart) ──
        $branchPeak = max(max($employees_by_branch->all() ?: [0]), 1);
        $activePct = $employees_total > 0 ? round(($employees_on_shift / $employees_total) * 100) : 0;
    @endphp

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <div class="text-[22px] font-extrabold tracking-tight">Dashboard</div>
            <div class="text-[13px] text-ink-2 mt-0.5">Monitor business health across all branches.</div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('branches.show', ['branch' => 3, 'tab' => 'workers']) }}" class="btn-pill">
                + Add Employee
            </a>
            <a href="{{ route('payments.index') }}" class="btn-pill">+ Record Payment</a>
            <a href="{{ route('receipts.index') }}" class="btn-pill">+ Scan Receipt</a>
            <a href="{{ route('branches') }}" class="btn-pill">+ Add Branch</a>
        </div>
    </div>

    @if($total_branches === 0)
        <div class="ncard"
            style="text-align:center;padding:32px 20px;margin-bottom:20px;background:linear-gradient(135deg,rgba(188,97,75,.04),rgba(188,97,75,.01));border:1.5px dashed var(--accent)">
            <div style="font-size:40px;margin-bottom:10px">🚀</div>
            <div style="font-size:17px;font-weight:800;color:var(--text);margin-bottom:6px">Welcome to InvenTrackr!</div>
            <div
                style="font-size:13px;color:var(--text-2);margin-bottom:18px;max-width:400px;margin-left:auto;margin-right:auto">
                Get started by adding your first branch. Once you have branches, this dashboard will show revenue, alerts, stock
                levels, and employee status across your business.</div>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                <a href="{{ route('setup.index') }}" class="btn-pill" style="text-decoration:none">🚀 Start the Setup Wizard</a>
                <a href="{{ route('branches') }}" class="btn-pill" style="text-decoration:none">+ Add Your First Branch</a>
                <a href="{{ route('receipts.index') }}" class="btn-pill" style="text-decoration:none">+ Scan a Receipt</a>
            </div>
            <div style="margin-top:18px;display:flex;gap:20px;justify-content:center;font-size:11px;color:var(--text-3)">
                <span>📊 Revenue tracking</span>
                <span>⚠️ Stock alerts</span>
                <span>👥 Employee management</span>
                <span>🗺️ Map view</span>
            </div>
        </div>
    @elseif($setup_needed)
        <div class="ncard"
            style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:16px 20px;margin-bottom:20px;border:1.5px dashed var(--accent)">
            <div>
                <div style="font-size:14px;font-weight:800;color:var(--text)">⚙️ Finish setting up your stock pipeline</div>
                <div style="font-size:12px;color:var(--text-2);margin-top:2px">
                    @if(!$setup_needed['has_ingredients']) Add your raw ingredients — @endif
                    @if(!$setup_needed['has_products']) build a product recipe so sales deduct stock — @endif
                    @if(!$setup_needed['has_stock']) and count your opening stock.
                    @else You're almost done — count your opening stock.
                    @endif
                </div>
            </div>
            <a href="{{ route('setup.index') }}" class="btn-pill" style="text-decoration:none">Open Setup Wizard →</a>
        </div>
    @endif

    {{-- ══ Branch Health Summary ══ --}}
    {{-- ══ Headline figures ══ --}}
    <div class="grid grid-cols-1 gap-5 mb-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="ncard relative">
            <div class="absolute top-4 right-4 bg-red-50 rounded-lg p-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-8 h-8 text-red-600">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                </svg>
            </div>
            <div class="nstat__label">Total Branches</div>
            <div class="nstat__value">{{ $total_branches }}</div>
            <span class="trend__note ml-0">active locations</span>
        </div>
        <div class="ncard relative">
            <div class="absolute top-4 right-4 bg-red-50 rounded-lg p-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-8 h-8 text-red-600">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
            </div>
            <div class="nstat__label">Pending Alerts</div>
            <div class="nstat__value">{{ $pending_alerts }}</div>
            @if ($delta_alerts)
                <span class="trend trend--{{ $delta_alerts['direction'] === 'up' ? 'down' : 'up' }}">
                    {{ $delta_alerts['pct'] }}%{{ $delta_alerts['direction'] === 'up' ? '↑' : '↓' }}
                </span>
                <span class="trend__note">vs last month</span>
            @else
                <span class="trend__note ml-0">unreviewed discrepancies</span>
            @endif
        </div>
        <div class="ncard relative">
            <div class="absolute top-4 right-4 bg-red-50 rounded-lg p-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-8 h-8 text-red-600">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
            </div>
            <div class="nstat__label">Low Stock Items</div>
            <div class="nstat__value">{{ $low_stock_count }}</div>
            <span class="trend__note ml-0">at or below threshold</span>
        </div>
        <div class="ncard relative">
            <div class="absolute top-4 right-4 bg-red-50 rounded-lg p-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-8 h-8 text-red-600">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
            <div class="text-base font-semibold">Employee Status</div>
            <div class="nstat__value" style="color: var(--color-ink); font-size: 36px;">{{ $employees_on_shift }}</div>
            <span class="text-[11px] font-semibold" style="color: #0d9488">{{ $activePct }}% active today</span>

            @if ($employees_by_branch->isNotEmpty())
                <div class="mt-3 flex h-[20px] items-end gap-1">
                    @foreach ($employees_by_branch as $label => $count)
                        <div class="gbar flex-1" style="height: {{ max(3, ($count / $branchPeak) * 18) }}px"
                            title="{{ $label }}: {{ $count }} on shift"></div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Two columns from 1024px up. Waiting for xl (1280px) left the right-hand
    rail stacked below on ordinary laptop widths, wasting most of the page. --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_300px]">

        {{-- ══════════ Left column ══════════ --}}
        <div class="flex flex-col gap-4">

            {{-- Recent Flag Summary --}}
            <div class="ncard">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-base font-semibold text-accent">Recent Flag Summary</span>
                    <span class="legend">
                        <span class="dot-item dot-item--low">Low Importance</span>
                        <span class="dot-item dot-item--med">Moderate Importance</span>
                        <span class="dot-item dot-item--high">High Importance</span>
                    </span>
                </div>

                @if ($branches_with_sales->isEmpty())
                    <div class="all-clear">No branches on record.</div>
                @else
                    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($branches_with_sales as $branch)
                            @php $sev = $flagged[$branch['name']] ?? null; @endphp
                            <a href="{{ route('alerts') }}" class="flex items-center gap-2 text-sm font-semibold hover:text-accent">
                                {{ $branch['name'] }}
                                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full"
                                    style="background: {{ $sev ? 'var(--color-sev-' . ($sevTone[$sev] ?? 'low') . ')' : 'var(--color-ink-3)' }}"></span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="nsection">
                Performance Summary <span class="nsection__pipe">|</span> {{ now()->format('F') }}
            </div>

            {{-- Total Revenue --}}
            <div class="ncard">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <div class="nstat__label">Total Revenue</div>
                        <div class="nstat__value" style="font-size: 24px;">{{ $peso($annual_revenue) }}</div>
                        @if ($delta_revenue)
                            <span class="trend trend--{{ $delta_revenue['direction'] }}">
                                {{ $delta_revenue['pct'] }}%{{ $delta_revenue['direction'] === 'up' ? '↑' : '↓' }}
                            </span>
                            <span class="trend__note">vs last month</span>
                        @else
                            <span class="trend__note ml-0">{{ $peso($revenue_this_month) }} this month</span>
                        @endif
                    </div>

                    <div class="md:border-l md:border-line md:pl-4">
                        <div class="nstat__label mb-1.5">Top Earner</div>
                        @if ($earners->isEmpty())
                            <div class="flex items-center gap-2 text-sm text-ink-3">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                    class="shrink-0 opacity-60">
                                    <line x1="12" y1="1" x2="12" y2="23" />
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                </svg>
                                No revenue recorded yet.
                            </div>
                        @else
                            <div class="rank-list">
                                @foreach ($earners as $b)
                                    <div class="rank-list__item">
                                        <span class="flex-1">{{ $b->name }}</span>
                                        <span class="font-semibold text-accent">{{ $peso($b->revenue) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-center md:border-l md:border-line md:pl-4">
                        <div class="goal">
                            <div class="nstat__label">Share</div>
                            @php
                                $topShare = $annual_revenue > 0 && $earners->isNotEmpty() ? (float) $earners->first()->revenue / $annual_revenue : 0;
                                $sharePercent = round($topShare * 100);
                            @endphp
                            <div style="position:relative; width:160px; height:80px; margin: 0 auto;">
                                <svg viewBox="0 0 160 80" width="160" height="80">
                                    <path d="M 10 80 A 70 70 0 0 1 150 80" fill="none" stroke="#e5e7eb" stroke-width="16"
                                        stroke-linecap="round" />
                                    <path d="M 10 80 A 70 70 0 0 1 150 80" fill="none" stroke="#c0392b" stroke-width="16"
                                        stroke-linecap="round" stroke-dasharray="{{ ($sharePercent / 100) * 220 }} 220" />
                                </svg>
                                <div style="position:absolute; bottom:0; width:100%; text-align:center;">
                                    <span
                                        style="font-size:20px; font-weight:700; color:#c0392b;">{{ $sharePercent }}%</span>
                                    <div style="font-size:10px; color:#6b7280;">from {{ $earners->first()->name ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Overall Value Saved --}}
            <div class="ncard">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <div class="nstat__label">Overall Value Saved</div>
                        <div class="nstat__value" style="font-size: 24px;">{{ $peso($value_saved) }}</div>
                        @if ($delta_leakage)
                            {{-- Less leakage month-over-month is the good direction. --}}
                            <span class="trend trend--{{ $delta_leakage['direction'] === 'up' ? 'down' : 'up' }}">
                                {{ $delta_leakage['pct'] }}%{{ $delta_leakage['direction'] === 'up' ? '↑' : '↓' }}
                            </span>
                            <span class="trend__note">leakage vs last month</span>
                        @else
                            <span class="trend__note ml-0">from reviewed alerts</span>
                        @endif
                    </div>

                    <div class="md:border-l md:border-line md:pl-4">
                        <div class="nstat__label mb-1.5">Least Leakage</div>
                        @if ($leakRows->isEmpty())
                            <div class="flex items-center gap-2 text-sm text-ink-3">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                    class="shrink-0 opacity-60">
                                    <line x1="18" y1="20" x2="18" y2="10" />
                                    <line x1="12" y1="20" x2="12" y2="4" />
                                    <line x1="6" y1="20" x2="6" y2="14" />
                                </svg>
                                No branches to compare yet.
                            </div>
                        @else
                            <div class="rank-list">
                                @foreach ($leakRows->take(6) as $row)
                                    @php $hasAlert = isset($flagged[$row['name']]); @endphp
                                    <div class="rank-list__item">
                                        <span class="flex-1">{{ $row['name'] }}</span>
                                        <span class="font-semibold {{ $hasAlert ? 'text-accent-2' : 'text-green' }}">
                                            {{ $hasAlert ? $peso($row['leak']) : 'Clean' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-center md:border-l md:border-line md:pl-4">
                        @php
                            $cleanCount = $leakRows->where('leak', '<=', 0)->count();
                            $cleanShare = $leakRows->count() > 0 ? $cleanCount / $leakRows->count() : 0;
                            $cleanPercent = round($cleanShare * 100);
                        @endphp
                        <div class="goal">
                            <div class="nstat__label">Clean</div>
                            <div style="position:relative; width:160px; height:80px; margin: 0 auto;">
                                <svg viewBox="0 0 160 80" width="160" height="80">
                                    <path d="M 10 80 A 70 70 0 0 1 150 80" fill="none" stroke="#e5e7eb" stroke-width="16"
                                        stroke-linecap="round" />
                                    <path d="M 10 80 A 70 70 0 0 1 150 80" fill="none" stroke="#10b981" stroke-width="16"
                                        stroke-linecap="round" stroke-dasharray="{{ ($cleanPercent / 100) * 220 }} 220" />
                                </svg>
                                <div style="position:absolute; bottom:0; width:100%; text-align:center;">
                                    <span
                                        style="font-size:20px; font-weight:700; color:#10b981;">{{ $cleanPercent }}%</span>
                                    <div style="font-size:10px; color:#6b7280;">{{ $cleanCount }}/{{ $leakRows->count() }}
                                        branches</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════ Right column ══════════ --}}
        <div class="flex flex-col gap-4">

            {{-- Employee Status --}}
            <div class="ncard">
                <div class="flex items-start justify-between mb-1">
                    <span class="text-base font-semibold">Employee Status</span>
                    <span class="text-xs font-semibold text-green">{{ $activePct }}% active today</span>
                </div>

                @if ($employees_by_branch->isEmpty())
                    <div class="all-clear">No branches on record.</div>
                @else
                    <div class="mt-4 flex h-[70px] items-end gap-3 px-1">
                        @foreach ($employees_by_branch as $label => $count)
                            <div class="flex flex-1 flex-col items-center">
                                <div class="gbar w-full" style="height: {{ max(4, ($count / $branchPeak) * 65) }}px"
                                    title="{{ $label }}: {{ $count }} on shift"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-3 px-1 text-center text-xs font-semibold text-ink-3">
                        @foreach ($employees_by_branch as $label => $count)
                            <span class="flex-1 truncate" title="{{ $label }}">{{ $label }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- KPI Performance --}}
            <div class="ncard flex-1">
                <div class="mb-1 flex items-center justify-between">
                    <span class="text-base font-semibold">
                        KPI Performance <span class="ml-1 text-xs font-medium text-ink-3">{{ now()->year }}</span>
                    </span>
                    <span class="pill-btn">Monthly</span>
                </div>

                <div class="mt-4 flex h-[110px] items-end gap-1.5">
                    @foreach ($series as $i => $v)
                        <div class="gbar {{ $i === $bestIx && $peak > 1 ? '' : 'gbar--soft' }} flex-1"
                            style="height: {{ max(4, ((float) $v / $peak) * 105) }}px"
                            title="{{ $months[$i] }} — {{ $peso($v) }}"></div>
                    @endforeach
                </div>
                <div class="mt-2 flex text-xs text-ink-3">
                    @foreach ($months as $i => $m)
                        <span
                            class="flex-1 text-center {{ $i === $bestIx && $peak > 1 ? 'font-bold text-accent' : '' }}">{{ $m }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection