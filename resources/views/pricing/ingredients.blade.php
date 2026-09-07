@extends('layouts.sidebar')

@section('title', 'Ingredient Pricing')

@php
    $allSuppliers = $suppliers ?? collect();
@endphp

@section('content')
<div class="mb-6">
    <div class="text-[22px] font-extrabold tracking-tight">Ingredient Pricing</div>
    <div class="text-[13px] text-ink-2 mt-0.5">Track per-package costs, delivery fees, and supplier pricing for each ingredient</div>
</div>

{{-- ══ Summary Cards ═══ --}}
<div class="grid grid-cols-[repeat(auto-fill,minmax(200px,1fr))] gap-4 mb-8">
    <div class="card p-4">
        <div class="text-[11px] font-bold uppercase tracking-[.04em] text-ink-3 mb-1">Total Ingredients</div>
        <div class="text-2xl font-extrabold">{{ $ingredients->count() }}</div>
    </div>
    <div class="card p-4">
        <div class="text-[11px] font-bold uppercase tracking-[.04em] text-ink-3 mb-1">With Supplier Link</div>
        <div class="text-2xl font-extrabold text-accent">{{ $ingredients->filter(fn ($i) => $i->suppliers->isNotEmpty())->count() }}</div>
    </div>
    <div class="card p-4">
        <div class="text-[11px] font-bold uppercase tracking-[.04em] text-ink-3 mb-1">Without Supplier</div>
        <div class="text-2xl font-extrabold {{ $ingredients->filter(fn ($i) => $i->suppliers->isEmpty())->count() > 0 ? 'text-accent-2' : 'text-green' }}">
            {{ $ingredients->filter(fn ($i) => $i->suppliers->isEmpty())->count() }}
        </div>
    </div>
    <div class="card p-4">
        <div class="text-[11px] font-bold uppercase tracking-[.04em] text-ink-3 mb-1">Used in Recipes</div>
        <div class="text-2xl font-extrabold">{{ $ingredients->filter(fn ($i) => $i->recipes->isNotEmpty())->count() }}</div>
    </div>
</div>

{{-- ══ Ingredient Pricing Table ═══ --}}
<div class="card">
    <div class="flex items-center justify-between px-5 py-4 border-b-[1.5px] border-line">
        <div>
            <div class="text-[15px] font-extrabold">Ingredient Price List</div>
            <div class="text-[11px] text-ink-3 mt-0.5">Per-unit cost, package size, and delivery fees</div>
        </div>
        <div class="flex items-center gap-2">
            <input type="text" class="form-input text-[12px] w-[200px]" placeholder="Search ingredients..." id="ingredientSearch">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Ingredient</th>
                    <th class="text-left">Unit</th>
                    <th class="text-left">Primary Supplier</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Package Size</th>
                    <th class="text-right">Package Price</th>
                    <th class="text-right">Delivery Fee</th>
                    <th class="text-center">Recipes</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ingredients as $ingredient)
                    @php
                        $primary = $ingredient->suppliers->first(fn ($s) => $s->pivot->is_primary) ?? $ingredient->suppliers->first();
                        $pkgSize = $primary?->pivot->package_size;
                        $pkgPrice = $primary?->pivot->package_price;
                        $delFee = $primary?->pivot->delivery_fee;
                    @endphp
                    <tr class="ingredient-row">
                        <td>
                            <div class="font-semibold text-[13px]">{{ $ingredient->name }}</div>
                        </td>
                        <td>
                            <span class="badge-gray">{{ strtoupper($ingredient->unit) }}</span>
                        </td>
                        <td>
                            @if($primary)
                                <div class="text-[13px] font-semibold">{{ $primary->name }}</div>
                                @if($primary->pivot->is_primary)
                                    <div class="text-[10px] text-accent font-bold">PRIMARY</div>
                                @endif
                            @else
                                <span class="text-[12px] text-ink-3 italic">No supplier linked</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($primary && $primary->pivot->unit_cost !== null)
                                <span class="font-bold text-[13px]">₱{{ number_format($primary->pivot->unit_cost, 2) }}</span>
                            @else
                                <span class="text-ink-3">—</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($pkgSize !== null)
                                <span class="text-[13px]">{{ number_format($pkgSize, 2) }} {{ strtoupper($ingredient->unit) }}</span>
                            @else
                                <span class="text-ink-3">—</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($pkgPrice !== null)
                                <span class="font-semibold text-[13px]">₱{{ number_format($pkgPrice, 2) }}</span>
                            @else
                                <span class="text-ink-3">—</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($delFee !== null)
                                <span class="text-[13px]">₱{{ number_format($delFee, 2) }}</span>
                            @else
                                <span class="text-ink-3">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge-gray">{{ $ingredient->recipes->count() }}</span>
                        </td>
                        <td class="text-center">
                            @if(auth()->user()->isSuperAdmin())
                            <button class="text-[11px] font-semibold text-accent hover:underline" onclick="openEditPricing({{ $ingredient->id }}, '{{ addslashes($ingredient->name) }}', '{{ strtoupper($ingredient->unit) }}')">Edit Pricing</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-10 text-ink-3 text-[13px]">No ingredients found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══ Edit Pricing Modal ═══ --}}
<div class="modal-overlay" id="editPricingModal">
    <div class="modal-content" style="max-width: 520px;">
        <span class="modal-badge">Edit Pricing</span>
        <h2 class="modal-title" id="editPricingTitle">Ingredient Pricing</h2>
        <p class="modal-subtitle">Set package details and delivery costs for this ingredient.</p>

        <form id="pricingForm" onsubmit="return savePricing(event)">
            <input type="hidden" id="pricingIngredientId">

            <div class="form-group mb-4">
                <label class="form-label">Supplier</label>
                <select class="form-input" id="pricingSupplier">
                    <option value="">— Select Supplier —</option>
                    @foreach($allSuppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Unit Cost (₱ per <span id="pricingUnitLabel">unit</span>)</label>
                <input type="number" step="0.01" min="0" class="form-input" id="pricingUnitCost" placeholder="0.00">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Package Size</label>
                    <input type="number" step="0.01" min="0" class="form-input" id="pricingPackageSize" placeholder="e.g. 500">
                </div>
                <div class="form-group">
                    <label class="form-label">Package Unit</label>
                    <select class="form-input" id="pricingPackageUnit">
                        <option value="">Same as ingredient</option>
                        <option value="g">Grams (g)</option>
                        <option value="kg">Kilograms (kg)</option>
                        <option value="ml">Milliliters (ml)</option>
                        <option value="l">Liters (L)</option>
                        <option value="pcs">Pieces (pcs)</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Package Price (₱)</label>
                    <input type="number" step="0.01" min="0" class="form-input" id="pricingPackagePrice" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Delivery Fee (₱)</label>
                    <input type="number" step="0.01" min="0" class="form-input" id="pricingDeliveryFee" placeholder="0.00">
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label flex items-center gap-2">
                    <input type="checkbox" id="pricingIsPrimary" class="accent-[var(--accent)]">
                    Set as primary supplier for this ingredient
                </label>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Notes</label>
                <textarea class="form-input" rows="2" id="pricingNotes" placeholder="e.g. Minimum order 10 packs, free delivery above ₱5,000"></textarea>
            </div>

            <div id="pricingError" class="text-[12px] text-red-600 mb-3" style="display:none;"></div>
            <div id="pricingSuccess" class="text-[12px] text-green mb-3" style="display:none;"></div>

            <div class="flex gap-3">
                <button type="button" class="btn-submit flex-1" onclick="closeEditPricing()" style="background: var(--border); color: var(--text);">Cancel</button>
                <button type="submit" class="btn-submit flex-1" id="pricingSaveBtn">Save Pricing</button>
            </div>

            {{-- Pricing History --}}
            <div id="pricingHistorySection" class="mt-6 pt-5 border-t-[1.5px] border-dashed border-line" style="display:none;">
                <div class="text-[13px] font-extrabold mb-3">Pricing History</div>
                <div id="pricingHistoryList" class="max-h-[200px] overflow-y-auto"></div>
            </div>
        </form>
    </div>
</div>

<script>
var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
var currentUnit = '';

function openEditPricing(id, name, unit) {
    currentUnit = unit;
    document.getElementById('pricingIngredientId').value = id;
    document.getElementById('editPricingTitle').textContent = name + ' — Pricing';
    document.getElementById('pricingUnitCost').placeholder = '0.00 per ' + unit;
    document.getElementById('pricingUnitLabel').textContent = unit;
    document.getElementById('pricingError').style.display = 'none';
    document.getElementById('pricingSuccess').style.display = 'none';

    // Load existing pricing data
    fetch('/pricing/ingredients/' + id + '/data', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.suppliers && data.suppliers.length > 0) {
            var s = data.suppliers[0];
            document.getElementById('pricingSupplier').value = s.id;
            document.getElementById('pricingUnitCost').value = s.unit_cost || '';
            document.getElementById('pricingPackageSize').value = s.package_size || '';
            document.getElementById('pricingPackageUnit').value = s.package_unit || '';
            document.getElementById('pricingPackagePrice').value = s.package_price || '';
            document.getElementById('pricingDeliveryFee').value = s.delivery_fee || '';
            document.getElementById('pricingIsPrimary').checked = s.is_primary || false;
            document.getElementById('pricingNotes').value = s.pricing_notes || '';
        } else {
            document.getElementById('pricingSupplier').value = '';
            document.getElementById('pricingUnitCost').value = '';
            document.getElementById('pricingPackageSize').value = '';
            document.getElementById('pricingPackageUnit').value = '';
            document.getElementById('pricingPackagePrice').value = '';
            document.getElementById('pricingDeliveryFee').value = '';
            document.getElementById('pricingIsPrimary').checked = false;
            document.getElementById('pricingNotes').value = '';
        }

        // Render pricing history
        var histSection = document.getElementById('pricingHistorySection');
        var histList = document.getElementById('pricingHistoryList');
        if (data.history && data.history.length > 0) {
            histSection.style.display = '';
            histList.innerHTML = data.history.map(function(h) {
                var date = new Date(h.created_at).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
                var costStr = h.unit_cost ? '\u20B1' + Number(h.unit_cost).toFixed(2) : '—';
                var pkgStr = h.package_price ? '\u20B1' + Number(h.package_price).toFixed(2) : '';
                var delStr = h.delivery_fee ? '\u20B1' + Number(h.delivery_fee).toFixed(2) : '';
                return '<div class="flex items-center justify-between py-2 border-b border-line text-[11px] last:border-b-0">' +
                    '<div><span class="font-semibold">' + costStr + '</span>' +
                    (pkgStr ? ' · pkg: ' + pkgStr : '') +
                    (delStr ? ' · del: ' + delStr : '') +
                    '<div class="text-ink-3">' + h.supplier_name + ' · ' + h.recorded_by + '</div></div>' +
                    '<div class="text-ink-3 text-right shrink-0 ml-3">' + date + '</div>' +
                '</div>';
            }).join('');
        } else {
            histSection.style.display = 'none';
        }
    })
    .catch(function() {
        document.getElementById('pricingHistorySection').style.display = 'none';
    });

    document.getElementById('editPricingModal').classList.add('active');
}

function closeEditPricing() {
    document.getElementById('editPricingModal').classList.remove('active');
}

document.getElementById('editPricingModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditPricing();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEditPricing();
});

async function savePricing(e) {
    e.preventDefault();
    var ingredientId = document.getElementById('pricingIngredientId').value;
    var supplierId = document.getElementById('pricingSupplier').value;
    var btn = document.getElementById('pricingSaveBtn');
    var errEl = document.getElementById('pricingError');
    var okEl = document.getElementById('pricingSuccess');

    errEl.style.display = 'none';
    okEl.style.display = 'none';

    if (!supplierId) {
        errEl.textContent = 'Please select a supplier.';
        errEl.style.display = 'block';
        return false;
    }

    btn.disabled = true;
    btn.textContent = 'Saving...';

    var body = {
        supplier_id: parseInt(supplierId),
        unit_cost: document.getElementById('pricingUnitCost').value || null,
        package_size: document.getElementById('pricingPackageSize').value || null,
        package_unit: document.getElementById('pricingPackageUnit').value || null,
        package_price: document.getElementById('pricingPackagePrice').value || null,
        delivery_fee: document.getElementById('pricingDeliveryFee').value || null,
        pricing_notes: document.getElementById('pricingNotes').value || null,
        is_primary: document.getElementById('pricingIsPrimary').checked,
    };

    try {
        var res = await fetch('/pricing/ingredients/' + ingredientId + '/pricing', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body)
        });
        var data = await res.json();
        if (res.ok && data.success) {
            okEl.textContent = data.message || 'Pricing saved!';
            okEl.style.display = 'block';
            setTimeout(function() {
                closeEditPricing();
                window.location.reload();
            }, 800);
        } else {
            var msg = data.message || 'Error saving pricing.';
            if (data.errors) {
                msg = Object.values(data.errors).flat().join(' ');
            }
            errEl.textContent = msg;
            errEl.style.display = 'block';
        }
    } catch (err) {
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Save Pricing';
    }
    return false;
}

// Search filter
document.getElementById('ingredientSearch').addEventListener('input', function(e) {
    var q = e.target.value.toLowerCase();
    document.querySelectorAll('.ingredient-row').forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
});
</script>
@endsection
