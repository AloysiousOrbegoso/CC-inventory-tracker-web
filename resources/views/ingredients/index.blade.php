@extends('layouts.sidebar')

@section('title', 'Ingredients')
@section('subtitle', 'Manage your raw materials inventory')

@section('content')
<div class="toolbar">
    <button class="btn-primary" onclick="openAddModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="vertical-align:middle;margin-right:6px;">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Ingredient
    </button>
</div>

<div class="card-panel">
    <table class="data-table" id="ingredient-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Unit</th>
                <th>Recipes Used In</th>
                <th>Created</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ingredients as $ingredient)
                <tr data-id="{{ $ingredient->id }}">
                    <td class="cell-primary">{{ $ingredient->name }}</td>
                    <td><span class="badge badge-gray">{{ $ingredient->unit }}</span></td>
                    <td>{{ $ingredient->recipes()->count() }}</td>
                    <td>{{ $ingredient->created_at?->format('M d, Y') ?? '—' }}</td>
                    <td style="text-align:right">
                        @if(auth()->user()->isManager())
                        <button class="btn-sm" onclick="openEditModal({{ $ingredient->id }}, '{{ addslashes($ingredient->name) }}', '{{ addslashes($ingredient->unit) }}')">Edit</button>
                        <button class="btn-sm danger" onclick="openDeleteModal({{ $ingredient->id }}, '{{ addslashes($ingredient->name) }}')">Delete</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state-icon" style="padding:32px 20px">
                            <div style="font-size:32px;margin-bottom:8px">🧂</div>
                            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No ingredients yet</div>
                            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">Add your raw materials to build recipes and track inventory.</div>
                            <div style="padding:10px 14px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:240px;margin:0 auto">
                                <div style="font-weight:600;color:var(--text);margin-bottom:4px">Units supported:</div>
                                <div>g, kg, ml, L, pcs, cup, tbsp, tsp, oz, lb</div>
                            </div>
                            <button class="btn-primary" style="margin-top:14px" onclick="openAddModal()">+ Add First Ingredient</button>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ════════════════════════════════════════════════
     ADD / EDIT MODAL
    ════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="ingredient-modal">
    <div class="modal">
        <div class="modal__head">
            <h2 class="modal__title" id="modal-title">Add Ingredient</h2>
            <button type="button" class="modal__close" onclick="closeModal('ingredient-modal')">&times;</button>
        </div>
        <form id="ingredient-form" onsubmit="return submitIngredient(event)">
            @csrf
            <input type="hidden" id="ingredient-id" value="">
            <div class="modal__body">
                <div class="form-group">
                    <label>Name <span class="required">*</span></label>
                    <input type="text" name="name" id="ingredient-name" class="form-control" placeholder="e.g. Matcha Powder" required>
                    <span class="form-error" id="error-name"></span>
                </div>
                <div class="form-group">
                    <label>Unit <span class="required">*</span></label>
                    <select name="unit" id="ingredient-unit" class="form-control" required>
                        <option value="">Select unit…</option>
                        <option value="g">g (grams)</option>
                        <option value="kg">kg (kilograms)</option>
                        <option value="ml">ml (milliliters)</option>
                        <option value="L">L (liters)</option>
                        <option value="pcs">pcs (pieces)</option>
                        <option value="cup">cup</option>
                        <option value="tbsp">tbsp (tablespoon)</option>
                        <option value="tsp">tsp (teaspoon)</option>
                        <option value="oz">oz (ounces)</option>
                        <option value="lb">lb (pounds)</option>
                    </select>
                    <span class="form-error" id="error-unit"></span>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn-secondary" onclick="closeModal('ingredient-modal')">Cancel</button>
                <button type="submit" class="btn-primary" id="submit-btn">Save Ingredient</button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════════
     DELETE CONFIRMATION MODAL
    ════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="delete-modal">
    <div class="modal">
        <div class="modal__head">
            <h2 class="modal__title">Delete Ingredient</h2>
            <button type="button" class="modal__close" onclick="closeModal('delete-modal')">&times;</button>
        </div>
        <div class="modal__body">
            <p style="font-size:14px;line-height:1.6;opacity:.8">
                Are you sure you want to delete <strong id="delete-name"></strong>?
                This action cannot be undone.
            </p>
        </div>
        <div class="modal__footer">
            <button type="button" class="btn-secondary" onclick="closeModal('delete-modal')">Cancel</button>
            <button type="button" class="btn-danger" id="delete-confirm-btn">Delete</button>
        </div>
        <form id="delete-form" method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

{{-- Toast container --}}
<div class="toast-container" id="toast-container"></div>

{{-- Modal styles --}}
<style>
    .modal-overlay {
        position: fixed; inset: 0; z-index: 999;
        background: rgba(44,24,16,.6); backdrop-filter: blur(4px);
        display: none; align-items: center; justify-content: center;
        padding: 20px;
    }
    .modal-overlay.is-open { display: flex; }

    .modal {
        background: #fff; border-radius: var(--radius);
        box-shadow: 0 16px 48px rgba(44,24,16,.25);
        width: 100%; max-width: 460px; max-height: 90vh; overflow-y: auto;
        padding: 28px 32px 24px;
    }

    .modal__head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 20px;
    }
    .modal__title { font-size: 17px; font-weight: 800; }
    .modal__close {
        width: 32px; height: 32px; border-radius: 8px;
        background: transparent; border: none; color: var(--brown);
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background .12s ease; font-size: 18px;
    }
    .modal__close:hover { background: rgba(92,45,27,.07); }
    .modal__body { display: flex; flex-direction: column; gap: 14px; }
    .modal__footer {
        display: flex; gap: 10px; justify-content: flex-end;
        margin-top: 20px; padding-top: 16px;
        border-top: 1px solid var(--border);
    }

    .form-group { display: flex; flex-direction: column; gap: 4px; }
    .form-group label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; opacity: .6;
    }
    .form-group label .required { color: #dc2626; }
    .form-control {
        padding: 10px 14px; border: 1.5px solid var(--border);
        border-radius: var(--radius-sm); font-size: 13px; font-weight: 500;
        font-family: var(--font); color: var(--brown); background: #fff;
        transition: border-color .15s ease; outline: none;
    }
    .form-control:focus { border-color: var(--terra); }
    .form-control.error { border-color: #dc2626; }
    select.form-control { cursor: pointer; appearance: auto; }
    .form-error { font-size: 11px; color: #dc2626; font-weight: 600; display: none; }
    .form-error.is-visible { display: block; }

    .toast-container {
        position: fixed; top: 20px; right: 20px; z-index: 1000;
        display: flex; flex-direction: column; gap: 8px;
    }
    .toast {
        padding: 12px 20px; border-radius: var(--radius-sm);
        font-size: 13px; font-weight: 600; color: #fff;
        box-shadow: 0 2px 8px rgba(92,45,27,.1), 0 8px 24px rgba(92,45,27,.07);
        animation: toast-in .25s ease; max-width: 360px;
    }
    .toast--success { background: #16a34a; }
    .toast--error { background: #dc2626; }

    @keyframes toast-in {
        from { opacity: 0; transform: translateX(40px); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>

<script>
var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
var _deleteIngredientId = null;

function openAddModal() {
    document.getElementById('ingredient-id').value = '';
    document.getElementById('ingredient-name').value = '';
    document.getElementById('ingredient-unit').value = '';
    document.getElementById('modal-title').textContent = 'Add Ingredient';
    document.getElementById('submit-btn').textContent = 'Save Ingredient';
    document.querySelectorAll('.form-error').forEach(function (e) { e.classList.remove('is-visible'); });
    document.querySelectorAll('.form-control.error').forEach(function (e) { e.classList.remove('error'); });
    document.getElementById('ingredient-modal').classList.add('is-open');
    document.getElementById('ingredient-name').focus();
}

function openEditModal(id, name, unit) {
    document.getElementById('ingredient-id').value = id;
    document.getElementById('ingredient-name').value = name;
    document.getElementById('ingredient-unit').value = unit;
    document.getElementById('modal-title').textContent = 'Edit Ingredient';
    document.getElementById('submit-btn').textContent = 'Update Ingredient';
    document.querySelectorAll('.form-error').forEach(function (e) { e.classList.remove('is-visible'); });
    document.querySelectorAll('.form-control.error').forEach(function (e) { e.classList.remove('error'); });
    document.getElementById('ingredient-modal').classList.add('is-open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('is-open');
}

function submitIngredient(event) {
    event.preventDefault();
    var id = document.getElementById('ingredient-id').value;
    var name = document.getElementById('ingredient-name').value.trim();
    var unit = document.getElementById('ingredient-unit').value;

    if (!name || !unit) {
        showToast('Please fill in all required fields.', 'error');
        return;
    }

    var btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = 'Saving…';

    var isEdit = !!id;
    var url = isEdit ? '/ingredients/' + id : '/ingredients';
    var method = isEdit ? 'PUT' : 'POST';

    var data = { name: name, unit: unit };

    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify(data)
    })
    .then(function (r) { return r.json().then(function (d) { d.status = r.status; return d; }); })
    .then(function (resp) {
        btn.disabled = false;
        btn.textContent = isEdit ? 'Update Ingredient' : 'Save Ingredient';

        if (resp.status >= 200 && resp.status < 300) {
            showToast(resp.message || 'Saved!', 'success');
            closeModal('ingredient-modal');
            refreshPage();
        } else if (resp.status === 422 && resp.errors) {
            // Show validation errors
            Object.keys(resp.errors).forEach(function (field) {
                var el = document.getElementById('error-' + field);
                var input = document.querySelector('[name="' + field + '"]');
                if (el) { el.textContent = resp.errors[field].join(', '); el.classList.add('is-visible'); }
                if (input) { input.classList.add('error'); }
            });
        } else {
            showToast(resp.message || 'Something went wrong.', 'error');
        }
    })
    .catch(function () {
        btn.disabled = false;
        btn.textContent = isEdit ? 'Update Ingredient' : 'Save Ingredient';
        showToast('Network error. Please try again.', 'error');
    });

    return false;
}

function openDeleteModal(id, name) {
    _deleteIngredientId = id;
    document.getElementById('delete-name').textContent = name;
    document.getElementById('delete-modal').classList.add('is-open');
}

document.getElementById('delete-confirm-btn')?.addEventListener('click', function () {
    if (!_deleteIngredientId) return;

    var btn = this;
    btn.disabled = true;
    btn.textContent = 'Deleting…';

    fetch('/ingredients/' + _deleteIngredientId, {
        method: 'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(function (r) { return r.json().then(function (d) { d.status = r.status; return d; }); })
    .then(function (resp) {
        btn.disabled = false;
        btn.textContent = 'Delete';

        if (resp.status >= 200 && resp.status < 300) {
            showToast(resp.message, 'success');
            closeModal('delete-modal');
            refreshPage();
        } else {
            showToast(resp.message || 'Failed to delete.', 'error');
        }
    })
    .catch(function () {
        btn.disabled = false;
        btn.textContent = 'Delete';
        showToast('Network error. Please try again.', 'error');
    });
});

function showToast(message, type) {
    var container = document.getElementById('toast-container');
    var toast = document.createElement('div');
    toast.className = 'toast toast--' + type;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 3000);
}

function refreshPage() {
    window.location.reload();
}

// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
        if (e.target === this) {
            this.classList.remove('is-open');
        }
    });
});

// Escape key closes modals
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.is-open').forEach(function (m) {
            m.classList.remove('is-open');
        });
    }
});
</script>
@endsection
