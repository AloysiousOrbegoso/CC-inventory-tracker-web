@extends('layouts.sidebar')

@section('title', 'Reports')

@section('content')
<style>
    /* ══ REPORTS-SPECIFIC STYLES ════════════════════════════════════════ */

    /* ══ BUSINESS TABS ══════════════════════════════════════════════════ */
    .business-tabs {
        display: flex;
        gap: 5px;
        margin-bottom: 24px;
        flex-wrap: wrap;
        border-bottom: 1px solid var(--border);
    }

    .business-tab {
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

    .business-tab:hover {
        opacity: 0.8;
    }

    .business-tab.active {
        opacity: 1;
        border-bottom-color: var(--terra);
    }

    .business-tab__icon {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
    }

    /* ══ FLAG LEGEND ════════════════════════════════════════════════════ */
    .flag-legend {
        display: flex;
        gap: 16px;
        font-size: 12px;
    }

    .flag-legend__item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .flag-legend__dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .flag-legend__dot.low { background: var(--yellow); }
    .flag-legend__dot.moderate { background: var(--orange); }
    .flag-legend__dot.high { background: var(--red); }

    /* ══ FLAG ITEMS ══════════════════════════════════════════════════════ */
    .flag-list {
        list-style: none;
    }

    .flag-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 14px 0;
        border-bottom: 1px solid var(--border);
    }

    .flag-item:last-child {
        border-bottom: none;
    }

    .flag-item__dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .flag-item__dot.low { background: var(--yellow); }
    .flag-item__dot.moderate { background: var(--orange); }
    .flag-item__dot.high { background: var(--red); }

    .flag-item__name {
        font-size: 14px;
        font-weight: 600;
        flex: 1;
    }

    .flag-item__date {
        font-size: 13px;
        opacity: .6;
        min-width: 100px;
    }

    .flag-item__link {
        font-size: 13px;
        color: var(--terra);
        text-decoration: none;
        font-weight: 600;
    }

    .flag-item__link:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .flag-item { flex-wrap: wrap; }
        .flag-item__date { width: 100%; margin-left: 28px; }
        .flag-item__link { margin-left: 28px; }
    }
</style>

{{-- ═══ PAGE TITLE ═══ --}}
<div class="mb-6">
    <div class="text-[22px] font-extrabold tracking-tight">Reports</div>
    <div class="text-[13px] text-ink-2 mt-0.5">Discrepancy flags across branches</div>
</div>

{{-- ═══ BUSINESS TABS ═══ --}}
<div class="business-tabs">
    <button class="business-tab active" data-business="flags" onclick="switchBusiness('flags', this)">
        <span class="business-tab__icon" style="background: #B45353; color: #fff;">F</span>
        Flags
    </button>
    @foreach($branches as $branch)
        <button class="business-tab {{ ($selectedBranchId ?? null) == $branch->id ? 'active' : '' }}" data-business="{{ $branch->id }}" onclick="switchBusiness('{{ $branch->id }}', this)">
            {{ $branch->name }}
        </button>
    @endforeach
</div>

{{-- ═══ RECENT FLAGS DETECTED ═══ --}}
<div class="card mb-5">
    <div class="card__head">
        <div class="card__title">Recent Flags Detected</div>
        <div class="flag-legend">
            <span class="flag-legend__item">
                <span class="flag-legend__dot low"></span>
                Low Importance
            </span>
            <span class="flag-legend__item">
                <span class="flag-legend__dot moderate"></span>
                Moderate Importance
            </span>
            <span class="flag-legend__item">
                <span class="flag-legend__dot high"></span>
                High Importance
            </span>
        </div>
    </div>
    <div class="card__body">
        @if($recentFlags->isEmpty())
            <div class="empty-state-icon" style="padding:24px 16px">
                <div style="font-size:24px;margin-bottom:6px">✅</div>
                <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px">No recent flags</div>
                <div style="font-size:11px;color:var(--text-2)">All stock levels are within normal range.</div>
            </div>
        @else
            <ul class="flag-list">
                @foreach($recentFlags as $flag)
                    <li class="flag-item">
                        <span class="flag-item__dot {{ $flag->severity }}"></span>
                        <span class="flag-item__name">{{ $flag->ingredient->name ?? 'Unknown' }} {{ ucfirst($flag->type ?? 'Discrepancy') }}</span>
                        <span class="flag-item__date">{{ $flag->created_at->format('m/ d/ Y') }}</span>
                        <span class="flag-item__name" style="flex: 0;">{{ $flag->branch->name ?? 'Unknown Branch' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

{{-- ═══ PREVIOUS FLAGS ═══ --}}
<div class="card mb-5">
    <div class="card__head">
        <div class="card__title">Previous Flags</div>
        <div class="flag-legend">
            <span class="flag-legend__item">
                <span class="flag-legend__dot low"></span>
                Low Importance
            </span>
            <span class="flag-legend__item">
                <span class="flag-legend__dot moderate"></span>
                Moderate Importance
            </span>
            <span class="flag-legend__item">
                <span class="flag-legend__dot high"></span>
                High Importance
            </span>
        </div>
    </div>
    <div class="card__body">
        @if($previousFlags->isEmpty())
            <div class="empty-state-icon" style="padding:24px 16px">
                <div style="font-size:24px;margin-bottom:6px">📋</div>
                <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px">No history yet</div>
                <div style="font-size:11px;color:var(--text-2)">Resolved or older flags will appear here.</div>
            </div>
        @else
            <ul class="flag-list">
                @foreach($previousFlags as $flag)
                    <li class="flag-item">
                        <span class="flag-item__dot {{ $flag->severity }}"></span>
                        <span class="flag-item__name">{{ $flag->ingredient->name ?? 'Unknown' }} {{ ucfirst($flag->type ?? 'Discrepancy') }}</span>
                        <span class="flag-item__date">{{ $flag->created_at->format('m/ d/ Y') }}</span>
                        <a href="{{ route('reports.flags.pdf', $flag->id) }}" class="flag-item__link">FlagReport.pdf</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<script>
    function switchBusiness(business, el) {
        // Update URL without reload
        var url = new URL(window.location.href);
        url.searchParams.set('branch_id', business);
        history.pushState({}, '', url.toString());

        // Update active tab styling
        document.querySelectorAll('.business-tab').forEach(function(t) {
            t.classList.remove('active');
        });
        el.classList.add('active');

        // Show loading state
        document.querySelectorAll('.card__body').forEach(function(body) {
            body.style.opacity = '0.4';
        });

        // Fetch new data via AJAX
        fetch('/ajax/reports?branch_id=' + business, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            renderReportsData(data);
            document.querySelectorAll('.card__body').forEach(function(body) {
                body.style.opacity = '1';
            });
        })
        .catch(function() {
            document.querySelectorAll('.card__body').forEach(function(body) {
                body.style.opacity = '1';
            });
        });
    }

    function renderReportsData(data) {
        // Render recent flags
        var recentContainer = document.querySelectorAll('.card__body')[0];
        if (recentContainer && data.recentFlags) {
            if (data.recentFlags.length === 0) {
                recentContainer.innerHTML = '<div class="empty-state-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg><span class="empty-state-text">None as of yet.</span></div>';
            } else {
                var html = '<ul class="flag-list">';
                data.recentFlags.forEach(function(flag) {
                    html += '<li class="flag-item">';
                    html += '<span class="flag-item__dot ' + flag.severity + '"></span>';
                    html += '<span class="flag-item__name">' + (flag.ingredient ? flag.ingredient.name : 'Unknown') + ' ' + (flag.type ? flag.type.charAt(0).toUpperCase() + flag.type.slice(1) : 'Discrepancy') + '</span>';
                    html += '<span class="flag-item__date">' + new Date(flag.created_at).toLocaleDateString('en-US', {month:'2-digit',day:'2-digit',year:'numeric'}) + '</span>';
                    html += '<span class="flag-item__name" style="flex:0;">' + (flag.branch ? flag.branch.name : 'Unknown Branch') + '</span>';
                    html += '</li>';
                });
                html += '</ul>';
                recentContainer.innerHTML = html;
            }
        }

        // Render previous flags
        var prevContainer = document.querySelectorAll('.card__body')[1];
        if (prevContainer && data.previousFlags) {
            if (data.previousFlags.length === 0) {
                prevContainer.innerHTML = '<div class="empty-state-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg><span class="empty-state-text">No previous flags.</span></div>';
            } else {
                var html2 = '<ul class="flag-list">';
                data.previousFlags.forEach(function(flag) {
                    html2 += '<li class="flag-item">';
                    html2 += '<span class="flag-item__dot ' + flag.severity + '"></span>';
                    html2 += '<span class="flag-item__name">' + (flag.ingredient ? flag.ingredient.name : 'Unknown') + ' ' + (flag.type ? flag.type.charAt(0).toUpperCase() + flag.type.slice(1) : 'Discrepancy') + '</span>';
                    html2 += '<span class="flag-item__date">' + new Date(flag.created_at).toLocaleDateString('en-US', {month:'2-digit',day:'2-digit',year:'numeric'}) + '</span>';
                    html2 += '<a href="/reports/flags/' + flag.id + '/pdf" class="flag-item__link">FlagReport.pdf</a>';
                    html2 += '</li>';
                });
                html2 += '</ul>';
                prevContainer.innerHTML = html2;
            }
        }
    }
</script>
@endsection
