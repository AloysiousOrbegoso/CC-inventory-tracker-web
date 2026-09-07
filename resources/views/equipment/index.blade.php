@extends('layouts.sidebar')

@section('title', 'Equipment Maintenance')

@section('styles')
    .eq-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    .eq-stat {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .eq-stat__value { font-size: 24px; font-weight: 800; color: var(--text); }
    .eq-stat__label { font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }

    .eq-filters {
        display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;
    }
    .eq-filter-btn {
        padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
        border: 1.5px solid var(--border); background: #fff; color: var(--text); cursor: pointer;
    }
    .eq-filter-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
    .eq-create-btn {
        margin-left: auto; padding: 8px 16px; border-radius: 8px; font-size: 12px;
        font-weight: 600; border: none; background: var(--accent); color: #fff; cursor: pointer;
    }

    .status-pill { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
    .status-pill.active { background: #dcfce7; color: #15803d; }
    .status-pill.maintenance_needed { background: #fef3c7; color: #b45309; }
    .status-pill.out_of_service { background: #fee2e2; color: #dc2626; }

    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-content { background: #fff; border-radius: 16px; padding: 32px; width: 90%; max-width: 600px; }
    .modal-title { font-size: 20px; font-weight: 700; color: var(--text); margin-bottom: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full { grid-column: span 2; }
    .form-label { font-size: 12px; font-weight: 600; color: var(--accent); }
    .form-input { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: var(--font); }
    .btn-submit { margin-top: 16px; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; background: #16a34a; color: #fff; cursor: pointer; width: 100%; }

    .flash-success { background: #dcfce7; border: 1px solid #16a34a; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
@endsection

@section('content')
@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

<div class="eq-summary">
    <div class="eq-stat"><div class="eq-stat__value">{{ $stats['total'] }}</div><div class="eq-stat__label">Total Equipment</div></div>
    <div class="eq-stat"><div class="eq-stat__value" style="color:#15803d">{{ $stats['active'] }}</div><div class="eq-stat__label">Active</div></div>
    <div class="eq-stat"><div class="eq-stat__value" style="color:#b45309">{{ $stats['maintenance_needed'] }}</div><div class="eq-stat__label">Needs Maintenance</div></div>
    <div class="eq-stat"><div class="eq-stat__value" style="color:#dc2626">{{ $stats['out_of_service'] }}</div><div class="eq-stat__label">Out of Service</div></div>
</div>

<div class="eq-filters">
    @foreach(['all' => 'All', 'active' => 'Active', 'maintenance_needed' => 'Needs Maintenance', 'out_of_service' => 'Out of Service'] as $key => $label)
        <button class="eq-filter-btn {{ ($status ?? 'all') === $key ? 'active' : '' }}" onclick="setStatus('{{ $key }}')">{{ $label }}</button>
    @endforeach
    @if(auth()->user()->isManager())
    <button class="eq-create-btn" onclick="openCreateModal()">+ Add Equipment</button>
    @endif
</div>

<div class="inv-panel">
    @if($equipment->isEmpty())
        <div class="empty-state" style="padding:32px">
            <div style="font-size:28px;margin-bottom:8px">🔧</div>
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No equipment yet</div>
            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">Track your kitchen equipment, maintenance schedules, and warranties.</div>
            <div style="padding:12px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:280px;margin:0 auto">
                <div style="font-weight:600;color:var(--text);margin-bottom:6px">📦 Equipment categories:</div>
                <div style="margin-bottom:3px">• Kitchen — ovens, blenders, grills</div>
                <div style="margin-bottom:3px">• Beverage — coffee machines, dispensers</div>
                <div style="margin-bottom:3px">• Cleaning — dishwashers, sanitizers</div>
                <div>• Furniture — tables, chairs, shelves</div>
            </div>
            <button class="eq-create-btn" style="margin-top:14px" onclick="openCreateModal()">+ Add First Equipment</button>
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Category</th><th>Branch</th><th>Status</th><th>Last Maintenance</th><th>Next Maintenance</th><th>Warranty</th></tr>
            </thead>
            <tbody>
                @foreach($equipment as $eq)
                    <tr>
                        <td style="font-weight:600">{{ $eq->name }}</td>
                        <td style="text-transform:capitalize">{{ $eq->category }}</td>
                        <td>{{ $eq->branch?->name ?? '—' }}</td>
                        <td><span class="status-pill {{ $eq->status }}">{{ str_replace('_', ' ', ucfirst($eq->status)) }}</span></td>
                        <td>{{ $eq->last_maintenance ? $eq->last_maintenance->format('M d, Y') : '—' }}</td>
                        <td>
                            @if($eq->next_maintenance)
                                <span style="{{ $eq->is_maintenance_due ? 'color:#dc2626;font-weight:700' : '' }}">
                                    {{ $eq->next_maintenance->format('M d, Y') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $eq->warranty_expiry ? $eq->warranty_expiry->format('M d, Y') : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $equipment->links() }}</div>
    @endif
</div>

<div class="modal-overlay" id="createModal">
    <div class="modal-content">
        <div class="modal-title">Add Equipment</div>
        <form action="{{ route('equipment.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <div class="form-group"><label class="form-label">Branch</label><select name="branch_id" class="form-input" required>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                <div class="form-group"><label class="form-label">Name</label><input type="text" name="name" class="form-input" placeholder="Equipment name" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Category</label><select name="category" class="form-input" required><option value="kitchen">Kitchen</option><option value="beverage">Beverage</option><option value="cleaning">Cleaning</option><option value="furniture">Furniture</option><option value="other">Other</option></select></div>
                <div class="form-group"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-input" placeholder="Optional"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" class="form-input"></div>
                <div class="form-group"><label class="form-label">Warranty Expiry</label><input type="date" name="warranty_expiry" class="form-input"></div>
            </div>
            <div class="form-row">
                <div class="form-group full"><label class="form-label">Next Maintenance Date</label><input type="date" name="next_maintenance" class="form-input"></div>
            </div>
            <button type="submit" class="btn-submit">Add Equipment</button>
        </form>
    </div>
</div>

<script>
    function openCreateModal() { document.getElementById('createModal').classList.add('active'); }
    document.getElementById('createModal').addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
    function setStatus(s) { const u = new URL(window.location.href); u.searchParams.set('status', s); window.location.href = u.toString(); }
</script>
@endsection
