@extends('layouts.sidebar')

@section('title', 'Audit Log #' . $log->id)

@section('styles')
    .audit-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    @media (max-width: 800px) { .audit-detail-grid { grid-template-columns: 1fr; } }

    .audit-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
    }
    .audit-panel__title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 16px;
    }
    .audit-info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }
    .audit-info-row:last-child { border-bottom: none; }
    .audit-info-row__label { color: var(--text-2); font-weight: 500; }
    .audit-info-row__value { font-weight: 600; color: var(--text); }

    .action-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .action-pill.create { background: #dcfce7; color: #15803d; }
    .action-pill.update { background: #dbeafe; color: #1d4ed8; }
    .action-pill.delete { background: #fee2e2; color: #dc2626; }

    .diff-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .diff-table th {
        text-align: left;
        padding: 8px 12px;
        background: var(--bg);
        font-weight: 600;
        color: var(--text-2);
        border-bottom: 1px solid var(--border);
    }
    .diff-table td {
        padding: 8px 12px;
        border-bottom: 1px solid var(--border);
    }
    .diff-old {
        background: #fef2f2;
        color: #991b1b;
        text-decoration: line-through;
    }
    .diff-new {
        background: #f0fdf4;
        color: #166534;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-2);
        text-decoration: none;
        margin-bottom: 16px;
    }
    .back-link:hover { color: var(--accent); }
@endsection

@section('content')
<a href="{{ route('audit.index') }}" class="back-link">← Back to Audit Trail</a>

<div class="audit-detail-grid">
    <!-- Log Info -->
    <div class="audit-panel">
        <div class="audit-panel__title">Audit Log Details</div>
        <div class="audit-info-row">
            <span class="audit-info-row__label">ID</span>
            <span class="audit-info-row__value">#{{ $log->id }}</span>
        </div>
        <div class="audit-info-row">
            <span class="audit-info-row__label">Timestamp</span>
            <span class="audit-info-row__value">{{ $log->created_at->format('M d, Y H:i:s') }}</span>
        </div>
        <div class="audit-info-row">
            <span class="audit-info-row__label">User</span>
            <span class="audit-info-row__value">{{ $log->user?->name ?? 'System' }}</span>
        </div>
        <div class="audit-info-row">
            <span class="audit-info-row__label">Action</span>
            <span class="audit-info-row__value">
                <span class="action-pill {{ $log->action }}">{{ ucfirst($log->action) }}</span>
            </span>
        </div>
        <div class="audit-info-row">
            <span class="audit-info-row__label">Model</span>
            <span class="audit-info-row__value">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</span>
        </div>
        @if($log->description)
        <div class="audit-info-row">
            <span class="audit-info-row__label">Description</span>
            <span class="audit-info-row__value">{{ $log->description }}</span>
        </div>
        @endif
        @if($log->ip_address)
        <div class="audit-info-row">
            <span class="audit-info-row__label">IP Address</span>
            <span class="audit-info-row__value">{{ $log->ip_address }}</span>
        </div>
        @endif
    </div>

    <!-- Changes -->
    <div class="audit-panel">
        <div class="audit-panel__title">Changes</div>
        @if($log->old_values || $log->new_values)
            <table class="diff-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $allKeys = array_unique(array_merge(
                            array_keys($log->old_values ?? []),
                            array_keys($log->new_values ?? [])
                        ));
                    @endphp
                    @foreach($allKeys as $key)
                        <tr>
                            <td style="font-weight:600">{{ $key }}</td>
                            <td class="{{ isset($log->old_values[$key]) ? 'diff-old' : '' }}">
                                {{ isset($log->old_values[$key]) ? ($log->old_values[$key] ?? 'null') : '—' }}
                            </td>
                            <td class="{{ isset($log->new_values[$key]) ? 'diff-new' : '' }}">
                                {{ isset($log->new_values[$key]) ? ($log->new_values[$key] ?? 'null') : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="text-align:center;color:var(--text-3);padding:20px;font-size:13px">
                No changes recorded.
            </div>
        @endif
    </div>
</div>

<!-- Raw Data -->
<div class="audit-panel">
    <div class="audit-panel__title">Raw Data</div>
    <pre style="background:var(--bg);padding:16px;border-radius:8px;font-size:11px;overflow-x:auto;color:var(--text-2)">{{ json_encode($log->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</div>
@endsection
