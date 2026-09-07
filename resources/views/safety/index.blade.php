@extends('layouts.sidebar')

@section('title', 'Health & Safety Checklists')

@section('styles')
    .sf-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    .sf-stat {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .sf-stat__value { font-size: 24px; font-weight: 800; color: var(--text); }
    .sf-stat__label { font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }

    .sf-create-btn {
        margin-left: auto; padding: 8px 16px; border-radius: 8px; font-size: 12px;
        font-weight: 600; border: none; background: var(--accent); color: #fff; cursor: pointer;
    }

    .status-pill { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
    .status-pill.pass { background: #dcfce7; color: #15803d; }
    .status-pill.partial { background: #fef3c7; color: #b45309; }
    .status-pill.fail { background: #fee2e2; color: #dc2626; }

    .flash-success { background: #dcfce7; border: 1px solid #16a34a; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
@endsection

@section('content')
@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

<div class="sf-summary">
    <div class="sf-stat"><div class="sf-stat__value">{{ $stats['total'] }}</div><div class="sf-stat__label">Total Checks</div></div>
    <div class="sf-stat"><div class="sf-stat__value" style="color:#15803d">{{ $stats['passed'] }}</div><div class="sf-stat__label">Passed</div></div>
    <div class="sf-stat"><div class="sf-stat__value" style="color:#dc2626">{{ $stats['failed'] }}</div><div class="sf-stat__label">Failed</div></div>
    <div class="sf-stat"><div class="sf-stat__value">{{ $stats['last_check'] ? $stats['last_check']->format('M d') : 'Never' }}</div><div class="sf-stat__label">Last Check</div></div>
</div>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
    @if(auth()->user()->isManager())
    <button class="sf-create-btn" onclick="window.location='{{ route('safety.create') }}'">+ New Checklist</button>
    @endif
</div>

<div class="inv-panel">
    @if($checklists->isEmpty())
        <div class="empty-state" style="padding:32px">
            <div style="font-size:28px;margin-bottom:8px">🛡️</div>
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No checklists yet</div>
            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">Create health and safety checklists to ensure compliance across branches.</div>
            <div style="padding:10px 14px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:260px;margin:0 auto">
                <div style="font-weight:600;color:var(--text);margin-bottom:4px">✅ Checklist items:</div>
                <div style="margin-bottom:3px">• Food handling & storage</div>
                <div style="margin-bottom:3px">• Equipment cleanliness</div>
                <div style="margin-bottom:3px">• Fire safety equipment</div>
                <div>• Personal hygiene standards</div>
            </div>
            <button class="sf-create-btn" style="margin-top:14px" onclick="window.location='{{ route('safety.create') }}'">+ Create First Checklist</button>
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr><th>Date</th><th>Branch</th><th>Completed By</th><th>Status</th><th>Items</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($checklists as $cl)
                    <tr>
                        <td style="font-weight:600">{{ $cl->check_date->format('M d, Y') }}</td>
                        <td>{{ $cl->branch?->name ?? '—' }}</td>
                        <td>{{ $cl->completer?->name ?? '—' }}</td>
                        <td><span class="status-pill {{ $cl->status }}">{{ ucfirst($cl->status) }}</span></td>
                        <td>
                            @php
                                $passed = collect($cl->items)->where('passed', true)->count();
                                $total = count($cl->items);
                            @endphp
                            {{ $passed }}/{{ $total }} passed
                        </td>
                        <td><a href="{{ route('safety.show', $cl) }}" style="font-size:12px;color:var(--accent);text-decoration:none">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $checklists->links() }}</div>
    @endif
</div>
@endsection
