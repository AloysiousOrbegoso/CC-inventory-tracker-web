@php
    $statusBadge = [
        'applied' => 'badge-gray',
        'shortlisted' => 'badge-blue',
        'interviewed' => 'badge-amber',
        'hired' => 'badge-green',
        'rejected' => 'badge-red',
    ];
@endphp
@extends('layouts.sidebar')

@section('title', 'Hiring')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="text-[22px] font-extrabold tracking-tight">Hiring</div>
        <div class="text-[13px] text-ink-2 mt-0.5">Job openings and applicant pipeline</div>
    </div>
    @if(auth()->user()->isManager())
    <button type="button" class="btn-primary" onclick="openOpeningModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Opening
    </button>
    @endif
</div>

<div class="grid grid-cols-4 max-[880px]:grid-cols-2 card border-[1.5px] border-line overflow-hidden divide-x divide-line max-[880px]:divide-x-0 mb-5">
    <div class="p-4 text-center max-[880px]:border-b max-[880px]:border-line">
        <div class="text-[26px] font-extrabold text-accent leading-none">{{ $kpi_open_positions }}</div>
        <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Open Positions</div>
    </div>
    <div class="p-4 text-center max-[880px]:border-b max-[880px]:border-line">
        <div class="text-[26px] font-extrabold text-accent leading-none">{{ $kpi_applicants }}</div>
        <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Applicants</div>
    </div>
    <div class="p-4 text-center">
        <div class="text-[26px] font-extrabold text-accent leading-none">{{ $kpi_in_interview }}</div>
        <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">In Interview</div>
    </div>
    <div class="p-4 text-center">
        <div class="text-[26px] font-extrabold text-green-600 leading-none">{{ $kpi_accepted }}</div>
        <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Accepted</div>
    </div>
</div>

<div class="utabs mb-4">
    <button type="button" class="utab is-active" id="tabOpenings" onclick="switchTab('openings')">Openings</button>
    <button type="button" class="utab" id="tabApplicants" onclick="switchTab('applicants')">Applicants</button>
    <button type="button" class="utab" id="tabPipeline" onclick="switchTab('pipeline')">Pipeline</button>
</div>

{{-- OPENINGS --}}
<div id="panelOpenings" class="summary-table-wrap">
    @if ($openings->isEmpty())
        <div class="empty-state-icon" style="padding:32px 20px">
            <div style="font-size:36px;margin-bottom:10px">💼</div>
            <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px">No job openings yet</div>
            <div style="font-size:12px;color:var(--text-2);margin-bottom:16px">Post your first opening to start collecting applicants.</div>
            <div style="padding:12px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:300px;margin:0 auto">
                <div style="font-weight:600;color:var(--text);margin-bottom:6px">🚀 Hiring pipeline:</div>
                <div style="margin-bottom:3px">• Post openings for specific branches</div>
                <div style="margin-bottom:3px">• Collect applicant resumes and info</div>
                <div style="margin-bottom:3px">• Track candidates through stages</div>
                <div>• Shortlist, interview, and hire — all in one place</div>
            </div>
            <button class="btn-primary" style="margin-top:14px" onclick="openOpeningModal()">+ Post First Opening</button>
        </div>
    @else
        <table class="summary-table">
            <thead><tr><th>Title</th><th>Branch</th><th>Status</th><th>Applicants</th><th></th></tr></thead>
            <tbody>
                @foreach ($openings as $opening)
                <tr>
                    <td class="font-bold">{{ $opening->title }}</td>
                    <td>{{ $opening->branch?->name ?? 'All branches' }}</td>
                    <td><span class="{{ $opening->status === 'open' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($opening->status) }}</span></td>
                    <td><span class="font-bold text-accent">{{ $opening->applicants->count() }}</span></td>
                    <td>
                        <div class="flex gap-1.5">
                            @if(auth()->user()->isManager())
                            <button class="btn-sm" onclick="openApplicantModal({{ $opening->id }}, '{{ addslashes($opening->title) }}')">Add Applicant</button>
                            @if(auth()->user()->isSuperAdmin())
                            <button class="btn-sm" onclick='openEditOpeningModal(@json($opening))'>Edit</button>
                            <button class="btn-sm danger" onclick="deleteOpening({{ $opening->id }}, '{{ addslashes($opening->title) }}')">Del</button>
                            @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- APPLICANTS --}}
<div id="panelApplicants" class="hidden">
    @if ($applicants->isNotEmpty())
        @include('partials._filter-toolbar', [
            'ft_id' => 'applicantsFilter',
            'ft_target' => '#applicantsTbody tr',
            'ft_searchPlaceholder' => 'Search applicant name…',
            'ft_branches' => $branches,
            'ft_statusOptions' => ['applied' => 'Applied', 'shortlisted' => 'Shortlisted', 'interviewed' => 'Interviewed', 'hired' => 'Hired', 'rejected' => 'Rejected'],
        ])
    @endif
    <div class="summary-table-wrap">
    @if ($applicants->isEmpty())
        <div class="empty-state-icon" style="padding:32px 20px">
            <div style="font-size:36px;margin-bottom:10px">📋</div>
            <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px">No applicants yet</div>
            <div style="font-size:12px;color:var(--text-2)">Applicants will appear here once people apply to your openings.</div>
        </div>
    @else
        <table class="summary-table">
            <thead><tr><th>Name</th><th>Opening</th><th>Contact</th><th>Status</th><th>Resume</th><th></th></tr></thead>
            <tbody id="applicantsTbody">
                @foreach ($applicants as $applicant)
                <tr data-search="{{ strtolower($applicant->name) }}" data-branch-id="{{ $applicant->opening?->branch_id }}" data-status="{{ $applicant->status }}" data-created="{{ $applicant->created_at->timestamp }}">
                    <td class="font-bold">{{ $applicant->name }}</td>
                    <td>{{ $applicant->opening?->title ?? '—' }}</td>
                    <td>{{ $applicant->email ?? '—' }}{{ $applicant->phone ? ' · '.$applicant->phone : '' }}</td>
                    <td>
                        <select class="form-input !h-8 !py-1 !text-[11px]" onchange="updateApplicantStatus({{ $applicant->id }}, this.value)">
                            @foreach (['applied','shortlisted','interviewed','hired','rejected'] as $st)
                                <option value="{{ $st }}" {{ $applicant->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        @if ($applicant->resume_path)
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($applicant->resume_path) }}" target="_blank" class="btn-sm">View</a>
                        @else
                            <span class="text-ink-3 text-xs">—</span>
                        @endif
                    </td>
                    <td>@if(auth()->user()->isManager())<button class="btn-sm danger" onclick="deleteApplicant({{ $applicant->id }}, '{{ addslashes($applicant->name) }}')">Del</button>@endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    </div>
</div>

{{-- PIPELINE (KANBAN) --}}
<div id="panelPipeline" class="hidden">
    @if ($applicants->isEmpty())
        <div class="empty-state-icon" style="padding:32px 20px">
            <div style="font-size:36px;margin-bottom:10px">🔀</div>
            <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px">Pipeline is empty</div>
            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">Once applicants start coming in, track them through each stage.</div>
            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;font-size:10px;font-weight:600">
                <span style="padding:6px 12px;background:var(--bg);border-radius:6px;color:var(--text-2)">Applied</span>
                <span style="color:var(--text-3)">→</span>
                <span style="padding:6px 12px;background:var(--bg);border-radius:6px;color:var(--text-2)">Shortlisted</span>
                <span style="color:var(--text-3)">→</span>
                <span style="padding:6px 12px;background:var(--bg);border-radius:6px;color:var(--text-2)">Interviewed</span>
                <span style="color:var(--text-3)">→</span>
                <span style="padding:6px 12px;background:#dcfce7;border-radius:6px;color:#15803d">Hired</span>
            </div>
        </div>
    @else
        <div class="grid grid-cols-5 max-[1100px]:grid-cols-1 gap-3">
            @foreach ($pipeline_stages as $stageKey => $stageLabel)
                @php $stageApplicants = $applicants->where('status', $stageKey); @endphp
                <div class="card border-[1.5px] border-line overflow-hidden">
                    <div class="px-3 py-2.5 border-b border-line flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-[.04em]">{{ $stageLabel }}</span>
                        <span class="text-[11px] font-bold text-accent">{{ $stageApplicants->count() }}</span>
                    </div>
                    <div class="p-2 flex flex-col gap-2 min-h-[60px]">
                        @forelse ($stageApplicants as $applicant)
                            <div class="p-2.5 bg-[rgba(92,45,27,.03)] border border-[rgba(92,45,27,.08)] rounded-lg">
                                <div class="text-[12.5px] font-bold truncate">{{ $applicant->name }}</div>
                                <div class="text-[10.5px] opacity-50 truncate">{{ $applicant->opening?->title ?? '—' }}</div>
                                @if (! in_array($stageKey, ['hired', 'rejected']))
                                    @php
                                        $stageOrder = ['applied', 'shortlisted', 'interviewed', 'hired'];
                                        $currentIdx = array_search($stageKey, $stageOrder);
                                        $nextStage = $stageOrder[$currentIdx + 1] ?? null;
                                    @endphp
                                    @if ($nextStage)
                                        <button type="button" class="btn-sm mt-1.5 w-full" onclick="updateApplicantStatus({{ $applicant->id }}, '{{ $nextStage }}', true)">
                                            Advance to {{ $pipeline_stages[$nextStage] }} →
                                        </button>
                                    @endif
                                @endif
                            </div>
                        @empty
                            <div class="text-[11px] opacity-30 text-center py-3">—</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- NEW/EDIT OPENING MODAL --}}
<div class="modal-overlay" id="openingModal">
    <div class="modal-box">
        <h2 class="text-lg font-extrabold mb-5" id="openingModalTitle">New Opening</h2>
        <form id="openingForm" onsubmit="saveOpening(event)">
            <input type="hidden" id="openingId">
            <div class="form-group"><div class="form-label">Title *</div><input type="text" class="form-input" id="opTitle" required placeholder="e.g. Barista — Full Time"></div>
            <div class="form-group">
                <div class="form-label">Branch <span class="opacity-50 font-normal">(leave blank for company-wide)</span></div>
                <select class="form-input" id="opBranch">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="opStatusGroup" style="display:none">
                <div class="form-label">Status</div>
                <select class="form-input" id="opStatus">
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="form-group"><div class="form-label">Description</div><textarea class="form-input" id="opDescription" placeholder="Role responsibilities, requirements…"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('openingModal')">Cancel</button>
                <button type="submit" class="btn-save">Save Opening</button>
            </div>
        </form>
    </div>
</div>

{{-- ADD APPLICANT MODAL --}}
<div class="modal-overlay" id="applicantModal">
    <div class="modal-box">
        <h2 class="text-lg font-extrabold mb-5">Add Applicant <span class="opacity-50 font-normal text-sm" id="applicantForOpening"></span></h2>
        <form id="applicantForm" onsubmit="saveApplicant(event)">
            <input type="hidden" id="applicantOpeningId">
            <div class="form-group"><div class="form-label">Name *</div><input type="text" class="form-input" id="apName" required></div>
            <div class="form-group"><div class="form-label">Email</div><input type="email" class="form-input" id="apEmail"></div>
            <div class="form-group"><div class="form-label">Phone</div><input type="text" class="form-input" id="apPhone"></div>
            <div class="form-group"><div class="form-label">Resume <span class="opacity-50 font-normal">(PDF/DOC — max 5MB)</span></div><input type="file" class="form-input" id="apResume" accept=".pdf,.doc,.docx"></div>
            <div class="form-group"><div class="form-label">Notes</div><textarea class="form-input" id="apNotes"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('applicantModal')">Cancel</button>
                <button type="submit" class="btn-save" id="applicantSubmitBtn">Add Applicant</button>
            </div>
        </form>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function switchTab(tab) {
    document.getElementById('tabOpenings').classList.toggle('is-active', tab === 'openings');
    document.getElementById('tabApplicants').classList.toggle('is-active', tab === 'applicants');
    document.getElementById('tabPipeline').classList.toggle('is-active', tab === 'pipeline');
    document.getElementById('panelOpenings').classList.toggle('hidden', tab !== 'openings');
    document.getElementById('panelApplicants').classList.toggle('hidden', tab !== 'applicants');
    document.getElementById('panelPipeline').classList.toggle('hidden', tab !== 'pipeline');
}

function closeModal(id) { document.getElementById(id).classList.remove('is-open'); }

function openOpeningModal() {
    document.getElementById('openingForm').reset();
    document.getElementById('openingId').value = '';
    document.getElementById('openingModalTitle').textContent = 'New Opening';
    document.getElementById('opStatusGroup').style.display = 'none';
    document.getElementById('openingModal').classList.add('is-open');
}
function openEditOpeningModal(opening) {
    document.getElementById('openingId').value = opening.id;
    document.getElementById('opTitle').value = opening.title;
    document.getElementById('opBranch').value = opening.branch_id ?? '';
    document.getElementById('opDescription').value = opening.description ?? '';
    document.getElementById('opStatus').value = opening.status;
    document.getElementById('openingModalTitle').textContent = 'Edit Opening';
    document.getElementById('opStatusGroup').style.display = '';
    document.getElementById('openingModal').classList.add('is-open');
}

async function saveOpening(e) {
    e.preventDefault();
    const id = document.getElementById('openingId').value;
    const body = {
        title: document.getElementById('opTitle').value,
        branch_id: document.getElementById('opBranch').value || null,
        description: document.getElementById('opDescription').value || null,
    };
    let url = '{{ route('hiring.openings.store') }}';
    let method = 'POST';
    if (id) {
        body.status = document.getElementById('opStatus').value;
        url = `{{ url('/hiring/openings') }}/${id}`;
        method = 'PUT';
    }
    const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify(body) });
    if (res.ok) location.reload(); else alert('Error saving opening.');
}

async function deleteOpening(id, title) {
    if (!confirm(`Delete opening "${title}"? This also removes its applicants.`)) return;
    const res = await fetch(`{{ url('/hiring/openings') }}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
    if (res.ok) location.reload();
}

function openApplicantModal(openingId, openingTitle) {
    document.getElementById('applicantForm').reset();
    document.getElementById('applicantOpeningId').value = openingId;
    document.getElementById('applicantForOpening').textContent = '— ' + openingTitle;
    document.getElementById('applicantModal').classList.add('is-open');
}

async function saveApplicant(e) {
    e.preventDefault();
    const openingId = document.getElementById('applicantOpeningId').value;
    const btn = document.getElementById('applicantSubmitBtn');
    btn.disabled = true; btn.textContent = 'Adding…';
    const fd = new FormData();
    fd.append('name', document.getElementById('apName').value);
    fd.append('email', document.getElementById('apEmail').value);
    fd.append('phone', document.getElementById('apPhone').value);
    fd.append('notes', document.getElementById('apNotes').value);
    const file = document.getElementById('apResume').files[0];
    if (file) fd.append('resume', file);
    const res = await fetch(`{{ url('/hiring/openings') }}/${openingId}/applicants`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd });
    if (res.ok) location.reload();
    else { alert('Error adding applicant.'); btn.disabled = false; btn.textContent = 'Add Applicant'; }
}

async function updateApplicantStatus(id, status, reload) {
    const res = await fetch(`{{ url('/hiring/applicants') }}/${id}/status`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify({ status }) });
    if (!res.ok) { alert('Error updating status.'); return; }
    if (reload) location.reload();
}

async function deleteApplicant(id, name) {
    if (!confirm(`Delete applicant "${name}"?`)) return;
    const res = await fetch(`{{ url('/hiring/applicants') }}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
    if (res.ok) location.reload();
}

document.querySelectorAll('.modal-overlay').forEach(el => el.addEventListener('click', e => { if (e.target === el) el.classList.remove('is-open'); }));
</script>
@endsection
