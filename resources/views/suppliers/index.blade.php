@extends('layouts.sidebar')

@section('title', 'Supplier Directory')

@section('styles')
<style>
    #supplierMiniMap { width: 100%; height: 180px; border-radius: 10px; border: 1.5px solid var(--border); }
</style>
@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="text-[22px] font-extrabold tracking-tight">Supplier Directory</div>
        <div class="text-[13px] text-ink-2 mt-0.5">Manage ingredient suppliers, contacts, and purchase history</div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('suppliers.import') }}" class="btn-sm flex items-center gap-1.5">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import CSV
        </a>
        @if(auth()->user()->isManager())
        <button class="btn-primary flex items-center gap-1.5" onclick="openAddModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Supplier
        </button>
        @endif
    </div>
</div>

<div class="grid grid-cols-[repeat(auto-fit,minmax(160px,1fr))] gap-3 mb-6">
    <div class="card p-5">
        <div class="text-[26px] font-extrabold">{{ $suppliers->count() }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Total Suppliers</div>
    </div>
    <div class="card p-5">
        <div class="text-[26px] font-extrabold">{{ $suppliers->where('is_active', true)->count() }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Active</div>
    </div>
    <div class="card p-5">
        <div class="text-[26px] font-extrabold">{{ $ingredients->count() }}</div>
        <div class="text-[11px] font-semibold text-ink-3 uppercase tracking-[.06em] mt-1">Ingredients Tracked</div>
    </div>
</div>

<div class="summary-table-wrap">
    @if ($suppliers->isEmpty())
        <div class="empty-state-icon" style="padding:32px 20px">
            <div style="font-size:32px;margin-bottom:8px">📦</div>
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No suppliers yet</div>
            <div style="font-size:12px;color:var(--text-2);margin-bottom:12px">Add your ingredient suppliers to track pricing, purchases, and link them to your inventory.</div>
            <div style="padding:10px 14px;background:var(--bg);border-radius:8px;text-align:left;font-size:11px;color:var(--text-2);max-width:260px;margin:0 auto">
                <div style="font-weight:600;color:var(--text);margin-bottom:4px">🔗 Supplier features:</div>
                <div style="margin-bottom:3px">• Link ingredients to suppliers</div>
                <div style="margin-bottom:3px">• Track purchase history & pricing</div>
                <div style="margin-bottom:3px">• Import suppliers from CSV/Excel</div>
                <div>• Geocode supplier locations on map</div>
            </div>
            <button class="btn-primary" style="margin-top:14px" onclick="openCreateModal()">+ Add First Supplier</button>
        </div>
    @else
        <table class="summary-table">
            <thead><tr><th>Supplier</th><th>Contact</th><th>Address / Landmark</th><th>Ingredients</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($suppliers as $supplier)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            @if ($supplier->photo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($supplier->photo_path) }}" alt="{{ $supplier->name }}"
                                     class="w-9 h-9 rounded-lg object-cover shrink-0 border border-line">
                            @else
                                <div class="w-9 h-9 rounded-lg shrink-0 bg-accent-light text-accent flex items-center justify-center text-[11px] font-extrabold uppercase">
                                    {{ mb_substr($supplier->name, 0, 2) }}
                                </div>
                            @endif
                            <div>
                                <div class="font-bold">{{ $supplier->name }}</div>
                                @if ($supplier->contact_person)<div class="text-ink-2 text-xs mt-0.5">{{ $supplier->contact_person }}</div>@endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $supplier->contact_number ?? '—' }}</td>
                    <td>{{ Str::limit($supplier->address, 30) }}@if ($supplier->landmark)<br><span class="opacity-60 text-[11px]">Near: {{ $supplier->landmark }}</span>@endif</td>
                    <td><span class="font-bold text-accent">{{ $supplier->ingredients_count }}</span></td>
                    <td>@if ($supplier->is_active)<span class="badge badge-green">Active</span>@else<span class="badge badge-gray">Inactive</span>@endif</td>
                    <td>
                        <div class="flex gap-1.5">
                            <button class="btn-sm" onclick="viewSupplier({{ $supplier->id }})">View</button>
                            @if(auth()->user()->isManager())
                            <button class="btn-sm" onclick="editSupplier({{ $supplier->id }}, {{ json_encode($supplier) }})">Edit</button>
                            @endif
                            <button class="btn-sm danger" onclick="deleteSupplier({{ $supplier->id }}, '{{ $supplier->name }}')">Del</button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ADD/EDIT MODAL --}}
<div class="modal-overlay" id="supplierModal">
    <div class="modal-box">
        <h2 class="text-lg font-extrabold mb-5" id="modalTitle">Add Supplier</h2>
        <form id="supplierForm" onsubmit="saveSupplier(event)">
            <input type="hidden" id="supplierId" value="">
            <div class="form-group"><div class="form-label">Supplier Name *</div><input type="text" class="form-input" id="fName" required placeholder="e.g. Marikina Market Vendor"></div>
            <div class="form-group"><div class="form-label">Contact Person</div><input type="text" class="form-input" id="fContactPerson" placeholder="e.g. Mang Juan"></div>
            <div class="form-group"><div class="form-label">Contact Number</div><input type="text" class="form-input" id="fContactNumber" placeholder="e.g. 0917-123-4567"></div>
            <div class="form-group"><div class="form-label">Address</div><textarea class="form-input" id="fAddress" placeholder="Full address..."></textarea></div>
            <div class="form-group">
                <div class="form-label">Auto-Geocode</div>
                <div class="flex gap-2 items-end">
                    <button type="button" class="btn-sm" onclick="geocodeSupplierAddress()" id="geocodeBtn">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align:-1px;margin-right:2px;"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Geocode
                    </button>
                    <span class="text-[11px] text-ink-3" id="geocodeStatus"></span>
                </div>
                <input type="hidden" id="fLatitude">
                <input type="hidden" id="fLongitude">
            </div>
            <div class="form-group"><div class="form-label">Nearest Landmark</div><input type="text" class="form-input" id="fLandmark" placeholder="e.g. Near BPI Marikina Market"></div>
            <div class="form-group"><div class="form-label">Notes</div><textarea class="form-input" id="fNotes" placeholder="Delivery schedule, minimum order, etc."></textarea></div>
            <div class="form-group"><div class="form-label">Photo <span class="opacity-50 font-normal">(storefront or contact, max 5MB)</span></div><input type="file" class="form-input" id="fPhoto" accept="image/*"></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

{{-- DETAIL MODAL --}}
<div class="modal-overlay" id="detailModal">
    <div class="modal-box" style="max-width:600px;">
        <div class="flex items-start justify-between mb-5 gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <img id="detailPhoto" src="" alt="" class="w-14 h-14 rounded-xl object-cover shrink-0 border border-line hidden">
                <div class="text-xl font-extrabold truncate" id="detailName">—</div>
            </div>
            <button class="btn-sm shrink-0" onclick="closeDetail()">Close</button>
        </div>
        <div class="mb-5"><div class="text-xs font-bold uppercase text-ink-3 mb-2">Contact Info</div><div class="text-[13px] leading-relaxed" id="detailContact">—</div></div>
        <div class="mb-5"><div class="text-xs font-bold uppercase text-ink-3 mb-2">Location</div><div class="text-[13px] leading-relaxed" id="detailLocation">—</div></div>
        <div class="mb-5"><div class="text-xs font-bold uppercase text-ink-3 mb-2">Map</div><div id="supplierMiniMap"></div></div>
        <div class="mb-5"><div class="text-xs font-bold uppercase text-ink-3 mb-2">Linked Ingredients</div><div class="flex flex-col gap-1.5" id="detailIngredients"><div class="p-4 text-center text-[13px] text-ink-3">No linked ingredients yet.</div></div></div>
        <div class="mb-5"><div class="text-xs font-bold uppercase text-ink-3 mb-2">Recent Purchases</div><div id="detailPurchases"><div class="p-4 text-center text-[13px] text-ink-3">No purchase history recorded.</div></div></div>
        <hr class="border-line my-5">
        <div class="mb-5">
            <div class="text-xs font-bold uppercase text-ink-3 mb-2">Link an Ingredient</div>
            <form id="linkForm" onsubmit="linkIngredient(event)" class="flex gap-2 flex-wrap items-end">
                <div class="flex-1 min-w-[140px]"><div class="form-label">Ingredient</div><select class="form-input" id="linkIngredientId" required onchange="updateUnitLabels()">@foreach ($ingredients as $ing)<option value="{{ $ing->id }}" data-unit="{{ $ing->unit }}">{{ $ing->name }} ({{ $ing->unit }})</option>@endforeach</select></div>
                <div class="w-[100px]"><div class="form-label">Unit Cost <span id="linkCostUnit" class="opacity-50 font-normal"></span></div><input type="number" step="0.01" class="form-input" id="linkCost" placeholder="₱"></div>
                <div class="flex items-center gap-1 pb-0.5"><input type="checkbox" id="linkPrimary"><label for="linkPrimary" class="text-[11px] font-semibold">Primary</label></div>
                <button type="submit" class="btn-save h-[42px]">Link</button>
            </form>
        </div>
        <div class="mt-4">
            <div class="text-xs font-bold uppercase text-ink-3 mb-2">Record a Purchase</div>
            <form id="purchaseForm" onsubmit="recordPurchase(event)" class="flex gap-2 flex-wrap items-end">
                <div class="flex-1 min-w-[140px]"><div class="form-label">Ingredient</div><select class="form-input" id="purchIngredient" required onchange="updateUnitLabels()">@foreach ($ingredients as $ing)<option value="{{ $ing->id }}" data-unit="{{ $ing->unit }}">{{ $ing->name }} ({{ $ing->unit }})</option>@endforeach</select></div>
                <div class="w-[90px]"><div class="form-label">Unit Price <span id="purchPriceUnit" class="opacity-50 font-normal"></span></div><input type="number" step="0.01" class="form-input" id="purchPrice" required placeholder="₱"></div>
                <div class="w-[80px]"><div class="form-label">Qty <span id="purchQtyUnit" class="opacity-50 font-normal"></span></div><input type="number" step="0.001" class="form-input" id="purchQty" required placeholder="0"></div>
                <div class="w-[130px]"><div class="form-label">Date</div><input type="date" class="form-input" id="purchDate" required value="{{ date('Y-m-d') }}"></div>
                <button type="submit" class="btn-save h-[42px]">Save</button>
            </form>
        </div>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
let currentSupplierId = null;

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Supplier';
    document.getElementById('supplierId').value = '';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierModal').classList.add('is-open');
}
function editSupplier(id, data) {
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    document.getElementById('supplierId').value = id;
    document.getElementById('fName').value = data.name || '';
    document.getElementById('fContactPerson').value = data.contact_person || '';
    document.getElementById('fContactNumber').value = data.contact_number || '';
    document.getElementById('fAddress').value = data.address || '';
    document.getElementById('fLandmark').value = data.landmark || '';
    document.getElementById('fNotes').value = data.notes || '';
    document.getElementById('fLatitude').value = data.latitude || '';
    document.getElementById('fLongitude').value = data.longitude || '';
    var geoStatus = document.getElementById('geocodeStatus');
    if (data.latitude && data.longitude) {
        geoStatus.textContent = 'Coords: ' + data.latitude + ', ' + data.longitude;
        geoStatus.style.color = 'var(--green)';
    } else {
        geoStatus.textContent = 'No coordinates set';
        geoStatus.style.color = '';
    }
    document.getElementById('supplierModal').classList.add('is-open');
}
function closeModal() { document.getElementById('supplierModal').classList.remove('is-open'); }

async function saveSupplier(e) {
    e.preventDefault();
    const id = document.getElementById('supplierId').value;

    // Sent as multipart so the optional photo rides along. PUT is spoofed via
    // _method because PHP does not parse multipart bodies on a real PUT.
    const fd = new FormData();
    fd.append('name', document.getElementById('fName').value);
    fd.append('contact_person', document.getElementById('fContactPerson').value);
    fd.append('contact_number', document.getElementById('fContactNumber').value);
    fd.append('address', document.getElementById('fAddress').value);
    fd.append('landmark', document.getElementById('fLandmark').value);
    fd.append('notes', document.getElementById('fNotes').value);

    var lat = document.getElementById('fLatitude').value;
    var lng = document.getElementById('fLongitude').value;
    if (lat) fd.append('latitude', lat);
    if (lng) fd.append('longitude', lng);

    const photo = document.getElementById('fPhoto').files[0];
    if (photo) fd.append('photo', photo);
    if (id) fd.append('_method', 'PUT');

    const url = id ? `/suppliers/${id}` : '/suppliers';
    const res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd });
    if (res.ok) location.reload(); else alert('Error saving supplier.');
}
async function deleteSupplier(id, name) {
    if (!confirm(`Delete supplier "${name}"?`)) return;
    const res = await fetch(`/suppliers/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
    if (res.ok) location.reload();
}
function updateUnitLabels() {
    const linkUnit = document.getElementById('linkIngredientId').selectedOptions[0]?.dataset.unit || '';
    document.getElementById('linkCostUnit').textContent = linkUnit ? `(per ${linkUnit})` : '';

    const purchUnit = document.getElementById('purchIngredient').selectedOptions[0]?.dataset.unit || '';
    document.getElementById('purchPriceUnit').textContent = purchUnit ? `(per ${purchUnit})` : '';
    document.getElementById('purchQtyUnit').textContent = purchUnit ? `(${purchUnit})` : '';
}

async function viewSupplier(id) {
    currentSupplierId = id;
    const res = await fetch(`/suppliers/${id}`, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return alert('Failed to load supplier.');
    const s = await res.json();
    document.getElementById('detailName').textContent = s.name;

    const photo = document.getElementById('detailPhoto');
    if (s.photo_path) {
        photo.src = `/storage/${s.photo_path}`;
        photo.alt = s.name;
        photo.classList.remove('hidden');
    } else {
        photo.classList.add('hidden');
    }

    document.getElementById('detailContact').innerHTML = `<strong>Person:</strong> ${s.contact_person||'—'}<br><strong>Phone:</strong> ${s.contact_number||'—'}`;
    document.getElementById('detailLocation').innerHTML = `${s.address||'—'}${s.landmark?'<br><strong>Landmark:</strong> '+s.landmark:''}`;
    document.getElementById('detailIngredients').innerHTML = s.ingredients&&s.ingredients.length ? s.ingredients.map(i=>`<div class="flex items-center justify-between p-2.5 px-3.5 bg-black/[.02] rounded-[10px] text-[13px]"><span class="font-semibold">${i.name} (${i.unit})</span><span class="flex items-center gap-2">${i.pivot.is_primary?'<span class="text-[10px] font-bold bg-[rgba(0,184,148,.1)] text-green px-2 py-0.5 rounded-full">PRIMARY</span>':''}<span class="font-bold text-accent">${i.pivot.unit_cost?'₱'+Number(i.pivot.unit_cost).toFixed(2):'—'}</span></span></div>`).join('') : '<div class="p-4 text-center text-[13px] text-ink-3">No linked ingredients.</div>';
    document.getElementById('detailPurchases').innerHTML = s.purchase_history&&s.purchase_history.length ? s.purchase_history.map(p=>`<div class="flex items-center justify-between py-2 border-b border-line text-xs last:border-b-0"><span>${new Date(p.purchased_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'})}</span><span>₱${Number(p.unit_price).toFixed(2)} × ${p.quantity}</span><span class="font-bold">₱${(p.unit_price*p.quantity).toFixed(2)}</span></div>`).join('') : '<div class="p-4 text-center text-[13px] text-ink-3">No purchases recorded.</div>';
    document.getElementById('detailModal').classList.add('is-open');
    updateUnitLabels();
}
function closeDetail() { document.getElementById('detailModal').classList.remove('is-open'); currentSupplierId = null; }

async function linkIngredient(e) {
    e.preventDefault();
    if (!currentSupplierId) return;
    const body = { ingredient_id: document.getElementById('linkIngredientId').value, unit_cost: document.getElementById('linkCost').value || null, is_primary: document.getElementById('linkPrimary').checked };
    const res = await fetch(`/suppliers/${currentSupplierId}/ingredients`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify(body) });
    if (res.ok) viewSupplier(currentSupplierId); else alert('Error linking ingredient.');
}
async function recordPurchase(e) {
    e.preventDefault();
    if (!currentSupplierId) return;
    const body = { ingredient_id: document.getElementById('purchIngredient').value, unit_price: document.getElementById('purchPrice').value, quantity: document.getElementById('purchQty').value, purchased_at: document.getElementById('purchDate').value };
    const res = await fetch(`/suppliers/${currentSupplierId}/purchases`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify(body) });
    if (res.ok) viewSupplier(currentSupplierId); else alert('Error recording purchase.');
}
document.querySelectorAll('.modal-overlay').forEach(el => { el.addEventListener('click', e => { if (e.target === el) el.classList.remove('is-open'); }); });

// ═══ Mini-Map (Google Maps) ═══
var supplierMap = null;
var supplierMapMarker = null;

function renderSupplierMiniMap(lat, lng, name) {
    var mapEl = document.getElementById('supplierMiniMap');
    if (!lat || !lng) {
        mapEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:180px;background:var(--bg);border-radius:10px;color:var(--text-3);font-size:12px;">No location data</div>';
        return;
    }

    mapEl.innerHTML = '';
    supplierMap = new google.maps.Map(mapEl, {
        center: { lat: parseFloat(lat), lng: parseFloat(lng) },
        zoom: 14,
        disableDefaultUI: true,
        gestureHandling: 'none'
    });

    supplierMapMarker = new google.maps.Marker({
        position: { lat: parseFloat(lat), lng: parseFloat(lng) },
        map: supplierMap,
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 11,
            fillColor: '#6366f1',
            fillOpacity: 1,
            strokeColor: '#fff',
            strokeWeight: 2
        },
        label: { text: 'S', color: '#fff', fontSize: '10px', fontWeight: '800' }
    });
}

// Patch viewSupplier to init mini-map
var _origViewSupplier = viewSupplier;
window.viewSupplier = async function(id) {
    currentSupplierId = id;
    var res = await fetch('/suppliers/' + id, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return alert('Failed to load supplier.');
    var s = await res.json();
    document.getElementById('detailName').textContent = s.name;

    var photo = document.getElementById('detailPhoto');
    if (s.photo_path) {
        photo.src = '/storage/' + s.photo_path;
        photo.alt = s.name;
        photo.classList.remove('hidden');
    } else {
        photo.classList.add('hidden');
    }

    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    var peso = '\u20B1';
    var times = '\u00D7';
    document.getElementById('detailContact').innerHTML = '<strong>Person:</strong> ' + esc(s.contact_person||'—') + '<br><strong>Phone:</strong> ' + esc(s.contact_number||'—');
    document.getElementById('detailLocation').innerHTML = esc(s.address||'—') + (s.landmark?'<br><strong>Landmark:</strong> ' + esc(s.landmark):'');
    document.getElementById('detailIngredients').innerHTML = s.ingredients&&s.ingredients.length ? s.ingredients.map(function(i){ return '<div class="flex items-center justify-between p-2.5 px-3.5 bg-black/[.02] rounded-[10px] text-[13px]"><span class="font-semibold">' + esc(i.name) + ' (' + esc(i.unit) + ')</span><span class="flex items-center gap-2">' + (i.pivot.is_primary?'<span class="text-[10px] font-bold bg-[rgba(0,184,148,.1)] text-green px-2 py-0.5 rounded-full">PRIMARY</span>':'') + '<span class="font-bold text-accent">' + (i.pivot.unit_cost?peso+Number(i.pivot.unit_cost).toFixed(2):'—') + '</span></span></div>'; }).join('') : '<div class="p-4 text-center text-[13px] text-ink-3">No linked ingredients.</div>';
    document.getElementById('detailPurchases').innerHTML = s.purchase_history&&s.purchase_history.length ? s.purchase_history.map(function(p){ return '<div class="flex items-center justify-between py-2 border-b border-line text-xs last:border-b-0"><span>' + new Date(p.purchased_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'}) + '</span><span>' + peso + Number(p.unit_price).toFixed(2) + ' ' + times + ' ' + p.quantity + '</span><span class="font-bold">' + peso + (p.unit_price*p.quantity).toFixed(2) + '</span></div>'; }).join('') : '<div class="p-4 text-center text-[13px] text-ink-3">No purchases recorded.</div>';

    // Render mini-map
    renderSupplierMiniMap(s.latitude, s.longitude, s.name);

    document.getElementById('detailModal').classList.add('is-open');
    updateUnitLabels();
};

async function geocodeSupplierAddress() {
    var address = document.getElementById('fAddress').value;
    var landmark = document.getElementById('fLandmark').value;
    var btn = document.getElementById('geocodeBtn');
    var status = document.getElementById('geocodeStatus');

    var query = address || landmark || document.getElementById('fName').value;
    if (!query) {
        status.textContent = 'Enter an address first';
        status.style.color = '#dc2626';
        return;
    }

    btn.disabled = true;
    btn.textContent = '...';
    status.textContent = 'Geocoding...';
    status.style.color = '';

    try {
        var res = await fetch('/api/geocode/address', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ address: query })
        });
        var data = await res.json();
        if (data.success) {
            document.getElementById('fLatitude').value = data.latitude;
            document.getElementById('fLongitude').value = data.longitude;
            status.textContent = data.latitude.toFixed(4) + ', ' + data.longitude.toFixed(4);
            status.style.color = '#16a34a';
        } else {
            status.textContent = data.message || 'Could not geocode';
            status.style.color = '#dc2626';
        }
    } catch (e) {
        status.textContent = 'Network error';
        status.style.color = '#dc2626';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align:-1px;margin-right:2px;"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Geocode';
    }
}
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key', '') }}"></script>
@endsection
