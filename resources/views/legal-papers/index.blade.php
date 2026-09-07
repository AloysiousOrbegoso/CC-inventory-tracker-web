@php
    $typeTag = [
        'permit' => 'tag--accent',
        'license' => 'tag--blue',
        'contract' => 'tag--amber',
        'insurance' => 'tag--blue',
        'tax' => 'tag--red',
        'other' => 'tag--accent',
    ];
@endphp
@extends('layouts.sidebar')

@section('title', 'Legal Papers')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="text-[22px] font-extrabold tracking-tight">Legal Papers</div>
        <div class="text-[13px] text-ink-2 mt-0.5">Permits, licenses, contracts, and insurance — with expiry tracking</div>
    </div>
    @if (auth()->user()->isSuperAdmin())
        <button type="button" class="btn-primary" onclick="openUploadModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Upload Document
        </button>
    @endif
</div>

<div class="grid grid-cols-4 max-[880px]:grid-cols-2 card border-[1.5px] border-line overflow-hidden divide-x divide-line max-[880px]:divide-x-0 mb-5">
    <div class="p-4 text-center max-[880px]:border-b max-[880px]:border-line">
        <div class="text-[26px] font-extrabold {{ $expiring_30_count > 0 ? 'text-amber-600' : 'text-accent' }} leading-none">{{ $expiring_30_count }}</div>
        <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Expiring in 30 Days</div>
    </div>
    <div class="p-4 text-center max-[880px]:border-b max-[880px]:border-line">
        <div class="text-[26px] font-extrabold text-accent leading-none">{{ $expiring_60_count }}</div>
        <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Expiring in 60 Days</div>
    </div>
    <div class="p-4 text-center">
        <div class="text-[26px] font-extrabold {{ $expired_count > 0 ? 'text-red-600' : 'text-accent' }} leading-none">{{ $expired_count }}</div>
        <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Expired Documents</div>
    </div>
    <div class="p-4 text-center">
        <div class="text-[26px] font-extrabold text-green-600 leading-none">{{ $active_count }}</div>
        <div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-50 mt-1.5">Active Documents</div>
    </div>
</div>

<div class="summary-table-wrap">
    @if ($documents->isEmpty())
        <div class="empty-state-icon" style="padding:32px 20px">
            <div style="font-size:32px;margin-bottom:8px">📑</div>
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No legal documents yet</div>
            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">Upload permits, licenses, and certificates to keep them organized and track expiry dates.</div>
            <div style="padding:10px 14px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:260px;margin:0 auto">
                <div style="font-weight:600;color:var(--text);margin-bottom:4px">📄 Document types:</div>
                <div style="margin-bottom:3px">• Business Permits</div>
                <div style="margin-bottom:3px">• BIR Registration (COR)</div>
                <div style="margin-bottom:3px">• DTI / SEC Certificates</div>
                <div>• Insurance Policies</div>
            </div>
        </div>
    @else
        <table class="summary-table">
            <thead><tr><th>Title</th><th>Type</th><th>Branch</th><th>Issued</th><th>Expires</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($documents as $doc)
                <tr>
                    <td class="font-bold">{{ $doc->title }}</td>
                    <td><span class="tag {{ $typeTag[$doc->type] ?? 'tag--accent' }}">{{ ucfirst($doc->type) }}</span></td>
                    <td>{{ $doc->branch?->name ?? 'All branches' }}</td>
                    <td>{{ $doc->issued_at?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $doc->expires_at?->format('M d, Y') ?? '—' }}</td>
                    <td>
                        @if ($doc->isExpired())
                            <span class="badge-red">Expired</span>
                        @elseif ($doc->isExpiringSoon())
                            <span class="badge-amber">Expiring Soon</span>
                        @else
                            <span class="badge-green">Valid</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex gap-1.5">
                            <a href="{{ route('legal-papers.download', $doc) }}" target="_blank" class="btn-sm">View</a>
                            @if (auth()->user()->isSuperAdmin())
                                <button class="btn-sm" onclick='openEditModal(@json($doc))'>Edit</button>
                                <button class="btn-sm danger" onclick="deleteDocument({{ $doc->id }}, '{{ addslashes($doc->title) }}')">Del</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- UPLOAD MODAL --}}
<div class="modal-overlay" id="uploadModal">
    <div class="modal-box">
        <h2 class="text-lg font-extrabold mb-5">Upload Document</h2>
        <form id="uploadForm" onsubmit="uploadDocument(event)">
            <div class="form-group"><div class="form-label">Title *</div><input type="text" class="form-input" id="upTitle" required placeholder="e.g. Business Permit 2026"></div>
            <div class="form-group">
                <div class="form-label">Type</div>
                <select class="form-input" id="upType">
                    <option value="permit">Permit</option>
                    <option value="license">License</option>
                    <option value="contract">Contract</option>
                    <option value="insurance">Insurance</option>
                    <option value="tax">Tax</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <div class="form-label">Branch <span class="opacity-50 font-normal">(leave blank for company-wide)</span></div>
                <select class="form-input" id="upBranch">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <div class="form-group flex-1"><div class="form-label">Issued</div><input type="date" class="form-input" id="upIssued"></div>
                <div class="form-group flex-1"><div class="form-label">Expires</div><input type="date" class="form-input" id="upExpires"></div>
            </div>
            <div class="form-group"><div class="form-label">File * <span class="opacity-50 font-normal">(PDF, JPG, PNG — max 5MB)</span></div><input type="file" class="form-input" id="upFile" accept=".pdf,.jpg,.jpeg,.png" required></div>
            <div class="form-group"><div class="form-label">Notes</div><textarea class="form-input" id="upNotes" placeholder="Optional notes…"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('uploadModal')">Cancel</button>
                <button type="submit" class="btn-save" id="uploadSubmitBtn">Upload</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h2 class="text-lg font-extrabold mb-5">Edit Document</h2>
        <form id="editForm" onsubmit="saveEdit(event)">
            <input type="hidden" id="editId">
            <div class="form-group"><div class="form-label">Title *</div><input type="text" class="form-input" id="editTitle" required></div>
            <div class="form-group">
                <div class="form-label">Type</div>
                <select class="form-input" id="editType">
                    <option value="permit">Permit</option>
                    <option value="license">License</option>
                    <option value="contract">Contract</option>
                    <option value="insurance">Insurance</option>
                    <option value="tax">Tax</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <div class="form-label">Branch</div>
                <select class="form-input" id="editBranch">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <div class="form-group flex-1"><div class="form-label">Issued</div><input type="date" class="form-input" id="editIssued"></div>
                <div class="form-group flex-1"><div class="form-label">Expires</div><input type="date" class="form-input" id="editExpires"></div>
            </div>
            <div class="form-group"><div class="form-label">Notes</div><textarea class="form-input" id="editNotes"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function openUploadModal() { document.getElementById('uploadForm').reset(); document.getElementById('uploadModal').classList.add('is-open'); }
function closeModal(id) { document.getElementById(id).classList.remove('is-open'); }

async function uploadDocument(e) {
    e.preventDefault();
    const btn = document.getElementById('uploadSubmitBtn');
    btn.disabled = true; btn.textContent = 'Uploading…';
    const fd = new FormData();
    fd.append('title', document.getElementById('upTitle').value);
    fd.append('type', document.getElementById('upType').value);
    fd.append('branch_id', document.getElementById('upBranch').value);
    fd.append('issued_at', document.getElementById('upIssued').value);
    fd.append('expires_at', document.getElementById('upExpires').value);
    fd.append('notes', document.getElementById('upNotes').value);
    fd.append('file', document.getElementById('upFile').files[0]);
    const res = await fetch('{{ route('legal-papers.store') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd });
    if (res.ok) location.reload();
    else { const data = await res.json(); alert(data.message || 'Error uploading document.'); btn.disabled = false; btn.textContent = 'Upload'; }
}

function openEditModal(doc) {
    document.getElementById('editId').value = doc.id;
    document.getElementById('editTitle').value = doc.title;
    document.getElementById('editType').value = doc.type;
    document.getElementById('editBranch').value = doc.branch_id ?? '';
    document.getElementById('editIssued').value = doc.issued_at ? doc.issued_at.slice(0, 10) : '';
    document.getElementById('editExpires').value = doc.expires_at ? doc.expires_at.slice(0, 10) : '';
    document.getElementById('editNotes').value = doc.notes ?? '';
    document.getElementById('editModal').classList.add('is-open');
}

async function saveEdit(e) {
    e.preventDefault();
    const id = document.getElementById('editId').value;
    const body = {
        title: document.getElementById('editTitle').value,
        type: document.getElementById('editType').value,
        branch_id: document.getElementById('editBranch').value || null,
        issued_at: document.getElementById('editIssued').value || null,
        expires_at: document.getElementById('editExpires').value || null,
        notes: document.getElementById('editNotes').value || null,
    };
    const res = await fetch(`{{ url('/legal-papers') }}/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify(body) });
    if (res.ok) location.reload(); else alert('Error saving document.');
}

async function deleteDocument(id, title) {
    if (!confirm(`Delete "${title}"?`)) return;
    const res = await fetch(`{{ url('/legal-papers') }}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
    if (res.ok) location.reload();
}

document.querySelectorAll('.modal-overlay').forEach(el => el.addEventListener('click', e => { if (e.target === el) el.classList.remove('is-open'); }));
</script>
@endsection
