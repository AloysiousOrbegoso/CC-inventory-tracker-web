@extends('layouts.sidebar')

@section('title', 'Safety Checklist')

@section('styles')
    .status-pill { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
    .status-pill.pass { background: #dcfce7; color: #15803d; }
    .status-pill.partial { background: #fef3c7; color: #b45309; }
    .status-pill.fail { background: #fee2e2; color: #dc2626; }

    .check-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        margin-bottom: 10px;
    }
    .check-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .check-icon.passed { background: #dcfce7; color: #15803d; }
    .check-icon.failed { background: #fee2e2; color: #dc2626; }

    .back-link { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--text-2); text-decoration: none; margin-bottom: 16px; }
    .back-link:hover { color: var(--accent); }
@endsection

@section('content')
<a href="{{ route('safety.index') }}" class="back-link">← Back to Checklists</a>

<div class="inv-panel" style="margin-bottom:20px">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <div style="font-size:18px;font-weight:700;color:var(--text)">Safety Checklist</div>
            <div style="font-size:13px;color:var(--text-2)">{{ $checklist->check_date->format('F d, Y') }} · {{ $checklist->branch?->name }}</div>
        </div>
        <span class="status-pill {{ $checklist->status }}">{{ ucfirst($checklist->status) }}</span>
    </div>
    <div style="font-size:12px;color:var(--text-3);margin-top:8px">Completed by {{ $checklist->completer?->name }}</div>
</div>

<div class="inv-panel">
    <div class="inv-panel__title">Checklist Items</div>

    @php
        $passed = collect($checklist->items)->where('passed', true)->count();
        $total = count($checklist->items);
    @endphp

    <div style="margin-bottom:16px;font-size:13px;font-weight:600;color:var(--text-2)">
        {{ $passed }}/{{ $total }} items passed
    </div>

    @foreach($checklist->items as $item)
    <div class="check-item">
        <div class="check-icon {{ $item['passed'] ? 'passed' : 'failed' }}">
            {{ $item['passed'] ? '✓' : '✗' }}
        </div>
        <div style="flex:1">
            <div style="font-size:13px;font-weight:600;color:var(--text)">{{ $item['item'] }}</div>
            @if(!empty($item['notes']))
                <div style="font-size:12px;color:var(--text-2);margin-top:4px">{{ $item['notes'] }}</div>
            @endif
        </div>
    </div>
    @endforeach

    @if($checklist->overall_notes)
    <div style="margin-top:20px;padding:16px;background:var(--bg);border-radius:8px">
        <div style="font-size:11px;font-weight:600;color:var(--text-3);text-transform:uppercase;margin-bottom:6px">Overall Notes</div>
        <div style="font-size:13px;color:var(--text-2)">{{ $checklist->overall_notes }}</div>
    </div>
    @endif
</div>
@endsection
