@extends('layouts.sidebar')

@section('title', 'Setup Wizard')
@section('subtitle', 'Three steps to a working stock pipeline')

@section('content')
@php
    $peso = fn ($n) => '₱' . number_format((float) $n, 2);
    $allDone = $status['has_ingredients'] && $status['has_products'] && $status['has_stock'];
@endphp

<div class="max-w-[1000px] mx-auto space-y-5">

    <div class="ncard p-5">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <div class="text-[19px] font-extrabold tracking-tight">Let's get you selling 🚀</div>
                <div class="text-[13px] text-ink-2 mt-0.5">The anti-theft engine needs three things before it can catch anything: ingredients, recipes, and opening stock.</div>
            </div>
            <button type="button" class="btn-sm" onclick="skipSetup()">Skip for now</button>
        </div>
    </div>

    {{-- ═══ STEP 1: INGREDIENTS ═══ --}}
    <div class="ncard p-5">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <div class="text-[15px] font-bold">1 · Raw ingredients <span id="step1-check" class="ml-1">{{ $status['has_ingredients'] ? '✅' : '' }}</span></div>
                <div class="text-[12px] text-ink-2">The materials you buy from suppliers — tea, milk, pearls, wrappers…</div>
            </div>
            <span class="badge badge-gray" id="step1-count">{{ \App\Models\Ingredient::count() }} on file</span>
        </div>
        <form id="ingredient-form" class="flex flex-wrap items-end gap-2" onsubmit="return submitIngredient(event)">
            @csrf
            <div class="flex-1 min-w-[180px]">
                <label class="text-[11px] font-semibold text-ink-2 block mb-1">Name</label>
                <input type="text" name="name" id="ing-name" class="form-control" placeholder="e.g. Tapioca Pearls" required>
            </div>
            <div class="w-[140px]">
                <label class="text-[11px] font-semibold text-ink-2 block mb-1">Unit</label>
                <select name="unit" id="ing-unit" class="form-control" required>
                    <option value="">Select…</option>
                    <option value="g">g (grams)</option>
                    <option value="kg">kg (kilograms)</option>
                    <option value="ml">ml (milliliters)</option>
                    <option value="L">L (liters)</option>
                    <option value="pcs">pcs (pieces)</option>
                    <option value="cup">cup</option>
                    <option value="tbsp">tbsp</option>
                    <option value="tsp">tsp</option>
                    <option value="oz">oz</option>
                    <option value="lb">lb</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Add</button>
        </form>
        <div class="form-error mt-2" id="ing-error"></div>
        @if ($ingredients->isNotEmpty())
        <div class="mt-3 flex flex-wrap gap-1.5">
            @foreach ($ingredients as $ingredient)
                <span class="badge badge-gray">{{ $ingredient->name }} <span class="opacity-60">({{ $ingredient->unit }})</span></span>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ═══ STEP 2: PRODUCT + RECIPE ═══ --}}
    <div class="ncard p-5 {{ $status['has_ingredients'] ? '' : 'opacity-50 pointer-events-none' }}">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <div class="text-[15px] font-bold">2 · Menu product & recipe <span id="step2-check">{{ $status['has_products'] ? '✅' : '' }}</span></div>
                <div class="text-[12px] text-ink-2">What you sell, and exactly how much of each ingredient one sale consumes — this is what makes automatic stock deduction work.</div>
            </div>
            <span class="badge badge-gray" id="step2-count">{{ \App\Models\Product::count() }} on file</span>
        </div>

        <form id="product-form" onsubmit="return submitProduct(event)">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3">
                <div class="md:col-span-2">
                    <label class="text-[11px] font-semibold text-ink-2 block mb-1">Product name</label>
                    <input type="text" name="name" id="prod-name" class="form-control" placeholder="e.g. Classic Milk Tea" required>
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-ink-2 block mb-1">Category</label>
                    <input type="text" name="category" id="prod-category" class="form-control" placeholder="Milk Tea">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[11px] font-semibold text-ink-2 block mb-1">Price ₱</label>
                        <input type="number" name="price" id="prod-price" class="form-control" step="0.01" min="0" placeholder="85" required>
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-ink-2 block mb-1">Large ₱</label>
                        <input type="number" name="price_large" id="prod-price-large" class="form-control" step="0.01" min="0" placeholder="optional">
                    </div>
                </div>
            </div>

            <div class="text-[11px] font-semibold text-ink-2 mb-1">Recipe — what one sale uses</div>
            <div id="recipe-lines" class="space-y-2">
                <div class="flex flex-wrap items-center gap-2 recipe-line" data-line>
                    <select class="form-control flex-1 min-w-[160px]" data-field="ingredient_id" required>
                        <option value="">Ingredient…</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                        @endforeach
                    </select>
                    <input type="number" class="form-control w-[110px]" data-field="quantity_required" step="0.001" min="0.001" placeholder="Amount" required>
                    <select class="form-control w-[110px]" data-field="size">
                        <option value="regular">Regular</option>
                        <option value="large">Large</option>
                    </select>
                    <button type="button" class="btn-sm" onclick="addRecipeLine()">+ Line</button>
                    <button type="button" class="btn-sm danger line-remove hidden" onclick="removeLine(this)">×</button>
                </div>
            </div>

            <div class="mt-3 flex items-center gap-3">
                <button type="submit" class="btn-primary">Save product & recipe</button>
                <span class="form-error" id="prod-error"></span>
            </div>
        </form>

        @if ($products->isNotEmpty())
        <div class="mt-3 text-[12px] text-ink-2">Recently added: {{ $products->pluck('name')->implode(', ') }}</div>
        @endif
    </div>

    {{-- ═══ STEP 3: OPENING STOCK ═══ --}}
    <div class="ncard p-5 {{ $status['has_ingredients'] ? '' : 'opacity-50 pointer-events-none' }}">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <div class="text-[15px] font-bold">3 · Opening stock <span id="step3-check">{{ $status['has_stock'] ? '✅' : '' }}</span></div>
                <div class="text-[12px] text-ink-2">Count what each branch physically has on hand right now. Shift-close variance checks start from this number.</div>
            </div>
        </div>

        @if ($branches->isEmpty())
            <div class="text-[12px] text-ink-2">No branches yet — <a href="{{ route('branches') }}" class="text-[#B45353] font-semibold">add a branch first</a>.</div>
        @else
        <form id="stock-form" onsubmit="return submitStock(event)">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
                <div>
                    <label class="text-[11px] font-semibold text-ink-2 block mb-1">Branch</label>
                    <select name="branch_id" id="stock-branch" class="form-control" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-ink-2 block mb-1">Low-stock alert at</label>
                    <input type="number" name="min_threshold" id="stock-threshold" class="form-control" step="0.001" min="0" placeholder="e.g. 500">
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-ink-2 block mb-1">Ingredient</label>
                    <select id="stock-ingredient" class="form-control">
                        <option value="">Ingredient…</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-end gap-2">
                <div class="w-[140px]">
                    <label class="text-[11px] font-semibold text-ink-2 block mb-1">Quantity on hand</label>
                    <input type="number" id="stock-qty" class="form-control" step="0.001" min="0" placeholder="0">
                </div>
                <button type="button" class="btn-secondary" onclick="addStockItem()">Add to list</button>
            </div>

            <div id="stock-items" class="mt-3 space-y-1.5"></div>
            <input type="hidden" name="items_json" id="items-json" value="[]">

            <div class="mt-3 flex items-center gap-3">
                <button type="submit" class="btn-primary">Save opening stock</button>
                <span class="text-[12px] text-ink-2">Already-stocked ingredients are skipped, never overwritten.</span>
            </div>
            <div class="form-error mt-2" id="stock-error"></div>
        </form>
        @endif
    </div>

    @if ($allDone)
    <div class="ncard p-5 text-center" style="background:linear-gradient(135deg,rgba(22,163,74,.06),transparent)">
        <div style="font-size:34px">🎉</div>
        <div class="text-[15px] font-bold">Pipeline live!</div>
        <div class="text-[12px] text-ink-2 mt-1">Every sale now deducts ingredients automatically, and shift closes will flag variances. Sales flow through the mobile POS API; watch results on <a href="{{ route('dashboard') }}" class="text-[#B45353] font-semibold">the dashboard</a>.</div>
    </div>
    @endif
</div>

<script>
const stockItems = [];

function refreshChecks(status) {
    if (!status) return;
    document.getElementById('step1-check').textContent = status.has_ingredients ? '✅' : '';
    document.getElementById('step2-check').textContent = status.has_products ? '✅' : '';
    document.getElementById('step3-check').textContent = status.has_stock ? '✅' : '';
}

async function submitIngredient(e) {
    e.preventDefault();
    const form = e.target;
    const fd = new FormData(form);
    const res = await fetch('{{ route('setup.ingredients.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
        body: fd,
    });
    const data = await res.json();
    if (!res.ok) { showErrors('ing-error', data); return false; }
    document.getElementById('ing-error').textContent = '';
    form.reset();
    // Add to the chip list + both dropdowns without a reload
    const chip = document.createElement('span');
    chip.className = 'badge badge-gray';
    chip.textContent = data.ingredient.name + ' (' + data.ingredient.unit + ')';
    document.querySelector('.ncard .flex.flex-wrap')?.appendChild(chip);
    for (const sel of document.querySelectorAll('#recipe-lines select[data-field=ingredient_id], #stock-ingredient')) {
        const opt = document.createElement('option');
        opt.value = data.ingredient.id;
        opt.textContent = data.ingredient.name + ' (' + data.ingredient.unit + ')';
        sel.appendChild(opt);
    }
    refreshChecks(data.status);
    return false;
}

function addRecipeLine() {
    const first = document.querySelector('#recipe-lines .recipe-line');
    const clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelector('.line-remove').classList.remove('hidden');
    clone.querySelector('button.btn-primary, button.btn-sm:not(.danger):not(.line-remove)')?.remove();
    document.getElementById('recipe-lines').appendChild(clone);
}

function removeLine(btn) { btn.closest('.recipe-line').remove(); }

async function submitProduct(e) {
    e.preventDefault();
    const lines = [...document.querySelectorAll('#recipe-lines .recipe-line')].map(row => ({
        ingredient_id: row.querySelector('[data-field=ingredient_id]').value,
        quantity_required: row.querySelector('[data-field=quantity_required]').value,
        size: row.querySelector('[data-field=size]').value,
    })).filter(l => l.ingredient_id && l.quantity_required);

    const res = await fetch('{{ route('setup.products.store') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Content-Type': 'application/json', 'Accept': 'application/json',
        },
        body: JSON.stringify({
            name: document.getElementById('prod-name').value,
            category: document.getElementById('prod-category').value,
            price: document.getElementById('prod-price').value,
            price_large: document.getElementById('prod-price-large').value || null,
            lines,
        }),
    });
    const data = await res.json();
    if (!res.ok) { showErrors('prod-error', data); return false; }
    document.getElementById('prod-error').textContent = '';
    e.target.reset();
    document.getElementById('recipe-lines').innerHTML = '';
    addRecipeLine();
    refreshChecks(data.status);
    return false;
}

function addStockItem() {
    const ingSel = document.getElementById('stock-ingredient');
    const qty = document.getElementById('stock-qty').value;
    const threshold = document.getElementById('stock-threshold').value;
    if (!ingSel.value || qty === '') { document.getElementById('stock-error').textContent = 'Pick an ingredient and quantity first.'; return; }
    document.getElementById('stock-error').textContent = '';
    const opt = ingSel.selectedOptions[0];
    stockItems.push({ ingredient_id: ingSel.value, quantity: qty, min_threshold: threshold || null });
    const row = document.createElement('div');
    row.className = 'text-[12px] flex items-center gap-2';
    row.innerHTML = '<span class="badge badge-gray">' + opt.textContent + ' — ' + qty + '</span>';
    row.querySelector('.badge').onclick = () => { stockItems.splice(stockItems.findIndex(i => i.ingredient_id === ingSel.value), 1); row.remove(); };
    document.getElementById('stock-items').appendChild(row);
    ingSel.value = ''; document.getElementById('stock-qty').value = '';
}

async function submitStock(e) {
    e.preventDefault();
    if (stockItems.length === 0) { document.getElementById('stock-error').textContent = 'Add at least one item to the list.'; return false; }
    const res = await fetch('{{ route('setup.stock.store') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Content-Type': 'application/json', 'Accept': 'application/json',
        },
        body: JSON.stringify({ branch_id: document.getElementById('stock-branch').value, items: stockItems }),
    });
    const data = await res.json();
    if (!res.ok) { showErrors('stock-error', data); return false; }
    document.getElementById('stock-error').textContent = '';
    stockItems.length = 0;
    document.getElementById('stock-items').innerHTML = '';
    alert(data.message);
    refreshChecks(data.status);
    return false;
}

async function skipSetup() {
    await fetch('{{ route('setup.skip') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
    });
    window.location.href = '{{ route('dashboard') }}';
}

function showErrors(id, data) {
    const el = document.getElementById(id);
    el.textContent = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Something went wrong.');
}
</script>
@endsection
