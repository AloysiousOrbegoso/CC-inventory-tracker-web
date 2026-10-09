@extends('layouts.sidebar')

@section('title', 'Mail/Messages')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="text-[22px] font-extrabold tracking-tight">Mail/Messages</div>
        <div class="text-[13px] text-ink-2 mt-0.5">Company-wide notices, posted for every branch to see</div>
    </div>
    <button type="button" class="btn-primary" onclick="openComposeModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Compose
    </button>
</div>

<div class="flex flex-col gap-3">
    @forelse ($notices as $notice)
        <div class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-[14px] font-extrabold">
                        {{ $notice->title }}
                    </div>

                    <div class="text-[11px] text-ink-3 mt-0.5">
                        {{ $notice->poster?->name ?? 'System' }}
                        ·
                        {{ $notice->branch?->name ?? 'All branches' }}
                        ·
                        {{ $notice->created_at->diffForHumans() }}
                    </div>
                </div>

                @if (auth()->id() === $notice->posted_by || auth()->user()->isSuperAdmin())
                    <button
                        type="button"
                        class="btn-sm danger shrink-0"
                        onclick="deleteNotice({{ $notice->id }}, @js($notice->title))"
                    >
                        Delete
                    </button>
                @endif
            </div>

            <p class="text-[13px] text-ink mt-2.5 leading-relaxed whitespace-pre-line">{{ $notice->body }}</p>
        </div>
    @empty
        <div class="card" style="text-align:center;padding:32px 20px">
            <div style="font-size:32px;margin-bottom:8px">📬</div>

            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">
                No messages yet
            </div>

            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">
                Post company-wide notices, schedule updates, or announcements for your team.
            </div>

            <div style="padding:10px 14px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:260px;margin:0 auto">
                <div style="font-weight:600;color:var(--text);margin-bottom:4px">
                    💡 Good for:
                </div>
                <div style="margin-bottom:3px">• Holiday schedule changes</div>
                <div style="margin-bottom:3px">• New policy announcements</div>
                <div style="margin-bottom:3px">• Team shoutouts and recognition</div>
                <div>• Urgent operational updates</div>
            </div>

            <button
                type="button"
                class="btn-primary"
                style="margin-top:14px"
                onclick="openComposeModal()"
            >
                + Post First Message
            </button>
        </div>
    @endforelse
</div>

{{-- DELETE NOTICE MODAL --}}
<div id="deleteNoticeModal" class="modal-overlay">
    <div class="modal-box w-full max-w-md">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
            <svg width="24" height="24" fill="none" stroke="currentColor"
                 stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>
                <path d="M10 11v5M14 11v5"/>
            </svg>
        </div>

        <h2 class="text-lg font-extrabold mb-2">Delete Notice?</h2>
        <p class="text-sm text-ink-2">
            Are you sure you want to delete
            <strong id="deleteNoticeTitle" class="text-ink"></strong>?
            This action cannot be undone.
        </p>

        <div class="modal-footer mt-6">
            <button type="button" class="btn-cancel" onclick="closeDeleteNoticeModal()">
                Cancel
            </button>
            <button type="button" id="confirmDeleteBtn" class="btn-save"
                    style="background:#dc2626" onclick="confirmDeleteNotice()">
                Delete Notice
            </button>
        </div>
    </div>
</div>

{{-- COMPOSE MODAL --}}
<div class="modal-overlay" id="composeModal">
    <div class="modal-box">
        <h2 class="text-lg font-extrabold mb-5">Compose Message</h2>
        <form id="composeForm" onsubmit="submitCompose(event)">
            <div class="form-group"><div class="form-label">Title *</div><input type="text" class="form-input" id="cTitle" required placeholder="e.g. Holiday schedule update"></div>
            @if ($branches->count() > 1)
            <div class="form-group">
                <div class="form-label">Branch <span class="opacity-50 font-normal">(leave blank for all branches)</span></div>
                <select class="form-input" id="cBranch">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="form-group"><div class="form-label">Message *</div><textarea class="form-input" id="cBody" rows="5" required placeholder="Write your message…"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save" id="composeSubmitBtn">Post Message</button>
            </div>
        </form>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function closeModal() { document.getElementById('composeModal').classList.remove('is-open'); }
function openComposeModal() { document.getElementById('composeForm').reset(); document.getElementById('composeModal').classList.add('is-open'); }

async function submitCompose(e) {
    e.preventDefault();
    const btn = document.getElementById('composeSubmitBtn');
    btn.disabled = true; btn.textContent = 'Posting…';
    const body = {
        title: document.getElementById('cTitle').value,
        body: document.getElementById('cBody').value,
        branch_id: document.getElementById('cBranch')?.value || null,
    };
    const res = await fetch('{{ route('notices.store') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify(body) });
    if (res.ok) location.reload();
    else { alert('Error posting message.'); btn.disabled = false; btn.textContent = 'Post Message'; }
}

let noticeToDelete = null;

function deleteNotice(id, title) {
    noticeToDelete = id;
    document.getElementById('deleteNoticeTitle').textContent = `"${title}"`;
    document.getElementById('deleteNoticeModal').classList.add('is-open');
}

function closeDeleteNoticeModal() {
    document.getElementById('deleteNoticeModal').classList.remove('is-open');
    noticeToDelete = null;
}

async function confirmDeleteNotice() {
    if (noticeToDelete === null) return;

    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.textContent = 'Deleting…';

    try {
        const res = await fetch(`{{ url('/mail') }}/${noticeToDelete}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            }
        });

        if (!res.ok) throw new Error();

        location.reload();
    } catch {
        alert('Failed to delete notice. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Delete Notice';
    }
}


document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => {
        if (e.target !== el) return;

        if (el.id === 'deleteNoticeModal') {
            closeDeleteNoticeModal();
        } else {
            el.classList.remove('is-open');
        }
    });
});
</script>
@endsection
