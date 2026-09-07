@extends('layouts.sidebar')

@section('title', 'Audit Trail')

@section('styles')
    .audit-filters {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        align-items: center;
    }
    .audit-filter-select {
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        font-family: var(--font);
    }
    .audit-filter-input {
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        font-family: var(--font);
    }
    .audit-filter-btn {
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        cursor: pointer;
        transition: all .15s;
    }
    .audit-filter-btn:hover { border-color: var(--accent); }
    .audit-filter-btn.active {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

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

    .model-tag {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        background: #f3f4f6;
        color: #374151;
        font-family: monospace;
    }

    .flash-success {
        background: #dcfce7;
        border: 1px solid #16a34a;
        color: #166534;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 13px;
    }
@endsection

@section('content')
@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

<!-- Filters -->
<div class="audit-filters">
    <select class="audit-filter-select" onchange="setFilter('action', this.value)">
        <option value="">All Actions</option>
        <option value="create" {{ ($selectedAction ?? '') === 'create' ? 'selected' : '' }}>Create</option>
        <option value="update" {{ ($selectedAction ?? '') === 'update' ? 'selected' : '' }}>Update</option>
        <option value="delete" {{ ($selectedAction ?? '') === 'delete' ? 'selected' : '' }}>Delete</option>
    </select>

    <select class="audit-filter-select" onchange="setFilter('model', this.value)">
        <option value="">All Models</option>
        @foreach($modelTypes as $type)
            <option value="{{ $type }}" {{ ($selectedModel ?? '') === $type ? 'selected' : '' }}>
                {{ class_basename($type) }}
            </option>
        @endforeach
    </select>

    <select class="audit-filter-select" onchange="setFilter('user_id', this.value)">
        <option value="">All Users</option>
        @foreach($users as $u)
            <option value="{{ $u->id }}" {{ ($selectedUserId ?? '') == $u->id ? 'selected' : '' }}>
                {{ $u->name }}
            </option>
        @endforeach
    </select>

    <input type="date" class="audit-filter-input" value="{{ $startDate ?? '' }}" onchange="setFilter('start_date', this.value)" placeholder="Start date">
    <input type="date" class="audit-filter-input" value="{{ $endDate ?? '' }}" onchange="setFilter('end_date', this.value)" placeholder="End date">
</div>

<!-- Audit Logs Table -->
<div class="inv-panel">
    @if($auditLogs->isEmpty())
        <div class="empty-state" style="padding:32px">
            <div style="font-size:28px;margin-bottom:8px">📋</div>
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No audit logs yet</div>
            <div style="font-size:12px;color:var(--text-2)">Activity will be recorded here as users make changes across the system.</div>
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Model</th>
                    <th>Description</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($auditLogs as $log)
                    <tr>
                        <td style="font-size:12px;color:var(--text-2)">
                            {{ $log->created_at->format('M d, Y H:i') }}
                        </td>
                        <td style="font-weight:600">{{ $log->user?->name ?? 'System' }}</td>
                        <td>
                            <span class="action-pill {{ $log->action }}">{{ ucfirst($log->action) }}</span>
                        </td>
                        <td>
                            <span class="model-tag">{{ class_basename($log->model_type) }}</span>
                            <span style="font-size:11px;color:var(--text-3);margin-left:4px">#{{ $log->model_id }}</span>
                        </td>
                        <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:var(--text-2)">
                            {{ $log->description ?? '—' }}
                        </td>
                        <td>
                            <a href="{{ route('audit.show', $log) }}" style="font-size:12px;color:var(--accent);text-decoration:none">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $auditLogs->links() }}</div>
    @endif
</div>

<script>
    function setFilter(key, value) {
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        window.location.href = url.toString();
    }
</script>
@endsection
