@extends('layouts.sidebar')

@section('title', 'Leave Requests')

@section('styles')
    /* ═══ HR SOFTWARE STYLE ═══ */
    .hr-grid {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 20px;
    }
    @media (max-width: 1024px) { .hr-grid { grid-template-columns: 1fr; } }

    /* Status summary — visual pipeline */
    .hr-pipeline {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
    }
    .hr-pipeline-card {
        flex: 1;
        padding: 16px;
        border-radius: 12px;
        border: 1.5px solid var(--border);
        background: #fff;
        text-align: center;
        cursor: pointer;
        transition: all .15s;
    }
    .hr-pipeline-card:hover { border-color: var(--accent); }
    .hr-pipeline-card.active { border-color: var(--accent); background: var(--color-accent-light); }
    .hr-pipeline-card__value { font-size: 28px; font-weight: 800; color: var(--text); }
    .hr-pipeline-card__label { font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }

    /* Leave request cards — people-centric */
    .hr-request-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all .15s;
    }
    .hr-request-card:hover { border-color: var(--accent); }
    .hr-request-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .hr-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--accent);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .hr-request-name { font-size: 14px; font-weight: 700; color: var(--text); }
    .hr-request-role { font-size: 11px; color: var(--text-3); }
    .hr-request-status {
        margin-left: auto;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .hr-request-status.pending { background: #fefce8; color: #a16207; }
    .hr-request-status.approved { background: #dcfce7; color: #15803d; }
    .hr-request-status.rejected { background: #fee2e2; color: #dc2626; }

    .hr-request-dates {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        background: #f9fafb;
        border-radius: 8px;
        margin-bottom: 12px;
    }
    .hr-request-dates__icon { font-size: 16px; }
    .hr-request-dates__text { font-size: 13px; font-weight: 600; color: var(--text); }
    .hr-request-dates__days {
        margin-left: auto;
        padding: 4px 10px;
        background: #fff;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        color: var(--accent);
    }

    .hr-request-type {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        background: #f3f4f6;
        color: var(--text-2);
        margin-bottom: 10px;
    }

    .hr-request-reason {
        font-size: 12px;
        color: var(--text-2);
        line-height: 1.5;
        margin-bottom: 12px;
    }

    .hr-request-actions {
        display: flex;
        gap: 8px;
        padding-top: 12px;
        border-top: 1px solid var(--border);
    }
    .hr-action-btn {
        flex: 1;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all .15s;
        text-align: center;
    }
    .hr-action-btn.approve { background: #dcfce7; color: #15803d; }
    .hr-action-btn.approve:hover { background: #bbf7d0; }
    .hr-action-btn.reject { background: #fee2e2; color: #dc2626; }
    .hr-action-btn.reject:hover { background: #fecaca; }
    .hr-action-btn.cancel { background: #f3f4f6; color: #6b7280; }
    .hr-action-btn.cancel:hover { background: #e5e7eb; }

    /* Sidebar */
    .hr-side-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
    }
    .hr-side-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 12px;
    }

    /* Team calendar preview */
    .hr-calendar-mini {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        margin-bottom: 12px;
    }
    .hr-calendar-day {
        text-align: center;
        padding: 6px 2px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .hr-calendar-day.header { color: var(--text-3); font-size: 10px; }
    .hr-calendar-day.today { background: var(--accent); color: #fff; }
    .hr-calendar-day.has-leave { background: #fef3c7; color: #92400e; }

    /* Filter bar */
    .hr-filter-bar {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        align-items: center;
    }
    .hr-filter-select {
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        font-family: var(--font);
    }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-content {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        width: 90%;
        max-width: 500px;
    }
    .modal-title { font-size: 20px; font-weight: 700; color: var(--text); margin-bottom: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .form-label { font-size: 12px; font-weight: 600; color: var(--accent); }
    .form-input { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: var(--font); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .btn-submit { margin-top: 12px; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; background: #16a34a; color: #fff; cursor: pointer; width: 100%; }

    .flash-success { background: #dcfce7; border: 1px solid #16a34a; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    .flash-error { background: #fee2e2; border: 1px solid #dc2626; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    .empty-state { text-align: center; color: var(--text-3); font-size: 13px; padding: 40px 20px; }
@endsection

@section('content')
@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="flash-error">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

{{-- ═══ FILTER BAR ═══ --}}
<div class="hr-filter-bar">
    @if(!$branches->isEmpty() && $branches->count() > 1)
        <select class="hr-filter-select" onchange="setBranch(this.value)">
            <option value="">All Branches</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
    @endif
    @if(auth()->user()->isManager() || auth()->user()->isStaff())
    <button class="hr-action-btn approve" style="margin-left:auto;flex:none;padding:8px 16px" onclick="openCreateModal()">+ Request Leave</button>
    @endif
</div>

{{-- ═══ STATUS PIPELINE ═══ --}}
<div class="hr-pipeline">
    <div class="hr-pipeline-card {{ ($status ?? 'all') === 'all' ? 'active' : '' }}" onclick="setStatus('all')">
        <div class="hr-pipeline-card__value">{{ $stats['total'] }}</div>
        <div class="hr-pipeline-card__label">All Requests</div>
    </div>
    <div class="hr-pipeline-card {{ ($status ?? '') === 'pending' ? 'active' : '' }}" onclick="setStatus('pending')">
        <div class="hr-pipeline-card__value" style="color:#a16207">{{ $stats['pending'] }}</div>
        <div class="hr-pipeline-card__label">Pending</div>
    </div>
    <div class="hr-pipeline-card {{ ($status ?? '') === 'approved' ? 'active' : '' }}" onclick="setStatus('approved')">
        <div class="hr-pipeline-card__value" style="color:#15803d">{{ $stats['approved'] }}</div>
        <div class="hr-pipeline-card__label">Approved</div>
    </div>
    <div class="hr-pipeline-card {{ ($status ?? '') === 'rejected' ? 'active' : '' }}" onclick="setStatus('rejected')">
        <div class="hr-pipeline-card__value" style="color:#dc2626">{{ $stats['rejected'] }}</div>
        <div class="hr-pipeline-card__label">Rejected</div>
    </div>
</div>

{{-- ═══ MAIN CONTENT ═══ --}}
<div class="hr-grid">
    <div>
        @if($leaveRequests->isEmpty())
            <div class="hr-side-panel">
                <div class="empty-state" style="padding:20px">
                    <div style="font-size:24px;margin-bottom:8px">📅</div>
                    <div style="font-weight:700;color:var(--text);margin-bottom:4px">No Leave Requests</div>
                    <div>Employees can request time off here.</div>
                    <div style="margin-top:16px;padding:12px;background:var(--bg);border-radius:8px;text-align:left;font-size:12px;color:var(--text-2)">
                        <div style="font-weight:600;color:var(--text);margin-bottom:6px">📅 Leave Types:</div>
                        <div>• <strong>Sick Leave</strong> — For health-related absences</div>
                        <div>• <strong>Vacation Leave</strong> — Planned time off</div>
                        <div>• <strong>Personal Leave</strong> — Personal matters</div>
                        <div>• <strong>Emergency Leave</strong> — Urgent situations</div>
                    </div>
                    <div style="margin-top:12px;padding:12px;background:var(--bg);border-radius:8px;text-align:left;font-size:12px;color:var(--text-2)">
                        <div style="font-weight:600;color:var(--text);margin-bottom:6px">👥 Manager Actions:</div>
                        <div>• Review pending requests</div>
                        <div>• Approve or reject with notes</div>
                        <div>• Track team availability</div>
                    </div>
                    <button class="hr-action-btn approve" style="margin-top:16px;flex:none;padding:8px 16px" onclick="openCreateModal()">+ Request Leave</button>
                </div>
            </div>
        @else
            @foreach($leaveRequests as $lr)
                <div class="hr-request-card">
                    <div class="hr-request-header">
                        <div class="hr-avatar">
                            {{ strtoupper(substr($lr->user?->name ?? '?', 0, 2)) }}
                        </div>
                        <div>
                            <div class="hr-request-name">{{ $lr->user?->name ?? '—' }}</div>
                            <div class="hr-request-role">{{ ucfirst($lr->user?->role ?? 'Staff') }} · {{ $lr->branch?->name ?? '—' }}</div>
                        </div>
                        <span class="hr-request-status {{ $lr->status }}">{{ ucfirst($lr->status) }}</span>
                    </div>

                    <div class="hr-request-dates">
                        <span class="hr-request-dates__icon">📅</span>
                        <span class="hr-request-dates__text">
                            {{ $lr->start_date->format('M d') }} – {{ $lr->end_date->format('M d, Y') }}
                        </span>
                        <span class="hr-request-dates__days">{{ $lr->days_count }} {{ Str::plural('day', $lr->days_count) }}</span>
                    </div>

                    <div class="hr-request-type">{{ ucfirst($lr->type) }} Leave</div>

                    @if($lr->reason)
                        <div class="hr-request-reason">{{ $lr->reason }}</div>
                    @endif

                    @if($isManager && $lr->status === 'pending')
                        <div class="hr-request-actions">
                            <form action="{{ route('leave.update-status', $lr) }}" method="POST" style="flex:1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="hr-action-btn approve" style="width:100%">✓ Approve</button>
                            </form>
                            <form action="{{ route('leave.update-status', $lr) }}" method="POST" style="flex:1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="hr-action-btn reject" style="width:100%">✗ Reject</button>
                            </form>
                        </div>
                    @elseif($lr->status === 'pending' && $lr->user_id === auth()->id())
                        <div class="hr-request-actions">
                            <form action="{{ route('leave.destroy', $lr) }}" method="POST" style="flex:1"
                                  onsubmit="return confirm('Cancel this leave request?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="hr-action-btn cancel" style="width:100%">Cancel Request</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
            <div style="margin-top:16px">{{ $leaveRequests->links() }}</div>
        @endif
    </div>

    {{-- ═══ SIDEBAR ═══ --}}
    <div>
        {{-- Pending Approvals --}}
        <div class="hr-side-panel">
            <div class="hr-side-title">⏳ Pending Approvals</div>
            @php
                $pendingRequests = $leaveRequests->where('status', 'pending');
            @endphp
            @if($pendingRequests->isEmpty())
                <div style="text-align:center;padding:16px">
                    <div style="font-size:20px;margin-bottom:6px">✅</div>
                    <div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:2px">All caught up</div>
                    <div style="font-size:11px;color:var(--text-3)">No pending leave requests.</div>
                </div>
            @else
                @foreach($pendingRequests->take(5) as $lr)
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
                        <div class="hr-avatar" style="width:32px;height:32px;font-size:11px">
                            {{ strtoupper(substr($lr->user?->name ?? '?', 0, 2)) }}
                        </div>
                        <div style="flex:1">
                            <div style="font-size:12px;font-weight:600;color:var(--text)">{{ $lr->user?->name ?? '—' }}</div>
                            <div style="font-size:11px;color:var(--text-3)">{{ $lr->type }} · {{ $lr->days_count }}d</div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Team Overview --}}
        <div class="hr-side-panel">
            <div class="hr-side-title">👥 Team Overview</div>
            <div style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:12px;color:var(--text-2)">Total Staff</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text)">{{ \App\Models\User::where('role', '!=', 'super_admin')->count() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:12px;color:var(--text-2)">On Leave Today</span>
                    <span style="font-size:12px;font-weight:700;color:#f97316">
                        {{ \App\Models\LeaveRequest::where('status', 'approved')->where('start_date', '<=', now())->where('end_date', '>=', now())->count() }}
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:12px;color:var(--text-2)">Available</span>
                    <span style="font-size:12px;font-weight:700;color:#16a34a">
                        {{ \App\Models\User::where('role', '!=', 'super_admin')->count() - \App\Models\LeaveRequest::where('status', 'approved')->where('start_date', '<=', now())->where('end_date', '>=', now())->count() }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Leave Types --}}
        <div class="hr-side-panel">
            <div class="hr-side-title">📊 Leave Types</div>
            @php
                $leaveTypes = $leaveRequests->groupBy('type')->map(fn($q) => $q->count());
            @endphp
            @if($leaveTypes->isEmpty())
                <div style="text-align:center;padding:16px">
                    <div style="font-size:20px;margin-bottom:6px">📊</div>
                    <div style="font-size:11px;color:var(--text-3)">No leave data yet.</div>
                </div>
            @else
                @foreach($leaveTypes as $type => $count)
                    <div style="display:flex;align-items:center;gap:10px;padding:6px 0">
                        <span style="font-size:12px;font-weight:600;color:var(--text);text-transform:capitalize;flex:1">{{ $type }}</span>
                        <span style="font-size:12px;font-weight:700;color:var(--accent)">{{ $count }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

{{-- ═══ CREATE MODAL ═══ --}}
<div class="modal-overlay" id="createModal">
    <div class="modal-content">
        <div class="modal-title">Request Leave</div>
        <form action="{{ route('leave.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Leave Type</label>
                <select name="type" class="form-input" required>
                    <option value="sick">Sick Leave</option>
                    <option value="vacation">Vacation Leave</option>
                    <option value="personal">Personal Leave</option>
                    <option value="emergency">Emergency Leave</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-input" required min="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-input" required min="{{ now()->format('Y-m-d') }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Reason (optional)</label>
                <textarea name="reason" class="form-input" rows="3" placeholder="Briefly describe the reason for your leave request"></textarea>
            </div>
            <button type="submit" class="btn-submit">Submit Request</button>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.add('active');
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.remove('active');
    }

    document.getElementById('createModal').addEventListener('click', function(e) {
        if (e.target === this) closeCreateModal();
    });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCreateModal(); });

    function setStatus(status) {
        const url = new URL(window.location.href);
        url.searchParams.set('status', status);
        window.location.href = url.toString();
    }
</script>
@endsection
