@php
    // ── Placeholder fallbacks ─────────────────────────────────────────
    if (!isset($branches) || $branches->isEmpty()) {
        $branches = collect([
            (object)['id'=>1,'name'=>'QC Main Branch'],
            (object)['id'=>2,'name'=>'Makati Outlet'],
            (object)['id'=>3,'name'=>'BGC Branch'],
            (object)['id'=>4,'name'=>'Cebu City Branch'],
        ]);
    }
    if (!isset($categories) || $categories->isEmpty()) {
        $categories = collect(['Drinks','Goods','Sets']);
    }
    if (!isset($allIngredients) || $allIngredients->isEmpty()) {
        $allIngredients = collect([
            (object)['id'=>1,'name'=>'Flour','unit'=>'g'],
            (object)['id'=>2,'name'=>'Sugar','unit'=>'g'],
            (object)['id'=>3,'name'=>'Butter','unit'=>'g'],
            (object)['id'=>4,'name'=>'Milk','unit'=>'ml'],
            (object)['id'=>5,'name'=>'Chocolate Syrup','unit'=>'ml'],
        ]);
    }

    if (!isset($products) || $products->isEmpty()) {
        $makeProd = fn($id,$name,$cat,$ings) => (object)[
            'id'=>$id, 'name'=>$name, 'category'=>$cat, 'price'=>0, 'procedure'=>'',
            'recipes'=>collect(array_map(fn($i)=>(object)[
                'id'=>0,
                'ingredient_id'=>0,
                'ingredient'=>(object)['id'=>0,'name'=>$i[0],'unit'=>$i[2]],
                'size'=>(object)['regular'=>$i[1],'large'=>$i[3]],
            ], $ings)),
        ];
        $products = collect([
            $makeProd(1,'Black Forest Milk Tea','Drinks',[
                ['Black Tea Base','200ml','ml','300ml'],
                ['Whole Milk','60ml','ml','90ml'],
                ['Chocolate Syrup','30ml','ml','45ml'],
                ['Cherry Syrup','15ml','ml','22ml'],
                ['Flavor Powder','25g','g','38g'],
                ['Sugar','20g','g','30g'],
                ['Ice','1 cup','cup','1.5 cups'],
            ]),
            $makeProd(2,'Classic Milk Tea','Drinks',[
                ['Black Tea Base','200ml','ml','300ml'],
                ['Whole Milk','80ml','ml','120ml'],
                ['Brown Sugar Syrup','25ml','ml','40ml'],
                ['Tapioca Pearls','50g','g','80g'],
                ['Ice','1 cup','cup','1.5 cups'],
            ]),
            $makeProd(3,'Taro Milk Tea','Drinks',[
                ['Taro Powder','30g','g','45g'],
                ['Whole Milk','100ml','ml','150ml'],
                ['Sugar Syrup','20ml','ml','30ml'],
                ['Ice','1 cup','cup','1.5 cups'],
            ]),
            $makeProd(4,'Cheese Dog Set','Sets',[
                ['Hotdog Bun','1 pc','pc','2 pc'],
                ['Hotdog','1 pc','pc','2 pc'],
                ['Cheese Sauce','30g','g','60g'],
                ['Ketchup','10g','g','20g'],
            ]),
        ]);
    }

    // ── Pre-compute JSON-safe product data for the edit modal ──────
    // (Must be AFTER the fallback block so placeholders are included.)
    $productsData = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->category,
            'price' => $p->price,
            'procedure' => $p->procedure ?? '',
            'recipes' => $p->recipes->map(function ($r) {
                return [
                    'id' => $r->id,
                    'ingredient_id' => $r->ingredient_id,
                    'size' => $r->size,
                    'quantity_required' => $r->quantity_required,
                    'ingredient' => $r->ingredient ? [
                        'id' => $r->ingredient_id,
                        'name' => $r->ingredient->name,
                        'unit' => $r->ingredient->unit,
                    ] : null,
                ];
            })->values(),
        ];
    })->values();
@endphp
@extends('layouts.sidebar')

@section('title', 'Recipes')

@section('content')
<style>@keyframes modalIn{from{opacity:0;transform:translateY(20px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}</style>

<div class="max-w-[1400px] mx-auto px-8 py-6 flex gap-5 max-[900px]:p-4">

    {{-- Branch Sidebar --}}
    <div class="w-[108px] shrink-0 bg-accent rounded-[var(--radius-card)] shadow-sm flex flex-col items-center px-2.5 py-3.5 gap-2.5 max-[900px]:hidden">
        @php $userBranchId = auth()->user()->branch_id; @endphp
        <div class="w-full bg-white rounded-[10px] border border-[rgba(92,45,27,.2)] px-2 py-2.5 text-center">
            <div class="mb-1.5">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#BC614B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                </svg>
            </div>
            <div class="text-[11px] font-bold leading-tight">{{ $branches->count() }} {{ Str::plural('Branch', $branches->count()) }}</div>
            <div class="text-[9px] font-semibold opacity-50 uppercase tracking-[.03em] mt-0.5">All Locations</div>
        </div>

        <div class="w-full h-px bg-white/20"></div>

        <div class="flex flex-col items-center gap-2 w-full">
            @foreach ($branches as $branch)
                @php $initials = collect(explode(' ', $branch->name))->map(fn($w) => strtoupper($w[0]))->take(2)->implode(''); @endphp
                <a href="#" class="{{ $branch->id === $userBranchId ? 'bg-[#5c2d1b] text-cream border-[#5c2d1b]' : 'bg-[rgba(250,249,247,.85)] text-[#5c2d1b] hover:bg-[#5c2d1b] hover:text-white hover:border-[#5c2d1b]' }} w-[42px] h-[42px] rounded-full border-[1.5px] border-[rgba(92,45,27,.3)] flex items-center justify-center text-[10px] font-bold cursor-pointer transition-all duration-150 no-underline shrink-0" title="{{ $branch->name }}">{{ $initials }}</a>
            @endforeach
        </div>
    </div>

    {{-- Main Content --}}
    <div class="flex-1 min-w-0 flex flex-col gap-5">

        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <div class="text-[22px] font-extrabold tracking-tight">Recipes</div>
                <div class="text-[13px] text-ink-2 mt-0.5">Manage recipes, pricing, and ingredient costs per product.</div>
            </div>
            @include('partials._business-tabs', ['active' => 'recipes'])
        </div>

        <div class="flex items-center gap-2.5 max-[900px]:flex-col max-[900px]:items-stretch">
            <input type="text" class="w-[280px] h-[38px] px-3.5 bg-white border-[1.5px] border-line rounded-lg text-[13px] text-[#5c2d1b] font-sans transition-[border-color] duration-150 placeholder:text-[rgba(92,45,27,.4)] focus:outline-none focus:border-accent max-[900px]:w-full" id="recipe-search" placeholder="Search product name…">
            <div class="flex gap-1.5" id="cat-pills">
                <span class="cat-pill py-[7px] px-[18px] rounded-full text-xs font-semibold border-[1.5px] cursor-pointer transition-all duration-150 bg-accent text-white border-accent" data-cat="">All</span>
                @foreach ($categories as $cat)
                    <span class="cat-pill py-[7px] px-[18px] rounded-full text-xs font-semibold border-[1.5px] cursor-pointer transition-all duration-150 bg-white text-[#5c2d1b] border-line" data-cat="{{ $cat }}">{{ $cat }}</span>
                @endforeach
            </div>
        </div>

        @forelse ($products as $product)
        <div class="recipe-card card overflow-hidden" data-cat="{{ $product->category }}" data-name="{{ strtolower($product->name) }}">
            <div class="flex items-center gap-2.5 px-6 py-[18px] border-b border-line">
                <svg class="shrink-0 opacity-60" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#BC614B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                    <line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/>
                </svg>
                <span class="text-base font-extrabold">{{ $product->name }}</span>

                @if (($product->availability ?? 'available') === 'discontinued')
                    <span class="badge-gray" title="This product has been discontinued.">Not Available</span>
                @elseif (($product->availability ?? 'available') === 'out_of_stock')
                    <span class="badge-red" title="Out of stock: {{ $product->missing_ingredients->implode(', ') }}">Not Available</span>
                @endif

                <span class="ml-auto text-[11px] font-semibold opacity-45 uppercase tracking-[.04em]">
                    &#8369;{{ number_format($product->price ?? 0, 2) }}
                    @if ($product->category)
                        &middot; {{ $product->category }}
                    @endif
                </span>
            </div>

            @if (($product->availability ?? 'available') === 'out_of_stock')
                <div class="flex items-start gap-2 px-6 py-2.5 bg-[rgba(214,48,49,.05)] border-b border-line text-[11px] font-semibold text-accent-2">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="mt-px shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Cannot be served — out of stock: {{ $product->missing_ingredients->implode(', ') }}</span>
                </div>
            @elseif (($product->availability ?? 'available') === 'discontinued')
                <div class="flex items-start gap-2 px-6 py-2.5 bg-black/[.03] border-b border-line text-[11px] font-semibold text-ink-2">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="mt-px shrink-0"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    <span>Discontinued — kept for reference only.</span>
                </div>
            @endif

            @if ($product->recipes->isEmpty())
                <div class="px-6 py-5 text-[13px] opacity-40">No recipe ingredients defined yet.</div>
            @else
                @php
                    $grouped = [];
                    foreach ($product->recipes as $r) {
                        $ingId = $r->ingredient_id;
                        if (!isset($grouped[$ingId])) {
                            $grouped[$ingId] = [
                                'ingredient' => $r->ingredient,
                                'regular' => null,
                                'large' => null,
                                'regular_id' => null,
                                'large_id' => null,
                            ];
                        }
                        if ($r->size === 'regular') {
                            $grouped[$ingId]['regular'] = $r->quantity_required;
                            $grouped[$ingId]['regular_id'] = $r->id;
                        } else {
                            $grouped[$ingId]['large'] = $r->quantity_required;
                            $grouped[$ingId]['large_id'] = $r->id;
                        }
                    }
                @endphp
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="text-left text-[10px] font-bold uppercase tracking-[.06em] opacity-45 px-6 py-3 bg-[rgba(92,45,27,.03)] border-b border-line">Ingredient</th>
                            <th class="text-left text-[10px] font-bold uppercase tracking-[.06em] opacity-45 px-6 py-3 bg-[rgba(92,45,27,.03)] border-b border-line"><span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-[.04em] bg-[rgba(92,45,27,.08)] text-[#5c2d1b]">Regular</span></th>
                            <th class="text-left text-[10px] font-bold uppercase tracking-[.06em] opacity-45 px-6 py-3 bg-[rgba(92,45,27,.03)] border-b border-line"><span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-[.04em] bg-[rgba(188,97,75,.12)] text-accent">Large</span></th>
                            <th class="text-left text-[10px] font-bold uppercase tracking-[.06em] opacity-45 px-6 py-3 bg-[rgba(92,45,27,.03)] border-b border-line">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grouped as $ingId => $g)
                            <tr>
                                <td class="px-6 py-4 text-[13px] align-top leading-[1.7] border-b border-[rgba(92,45,27,.06)]"><strong class="font-semibold">{{ $g['ingredient']->name ?? '—' }}</strong></td>
                                <td class="px-6 py-4 text-[13px] align-top leading-[1.7] border-b border-[rgba(92,45,27,.06)]">{{ $g['regular'] !== null ? rtrim(rtrim(number_format($g['regular'], 3), '0'), '.') : '—' }}</td>
                                <td class="px-6 py-4 text-[13px] align-top leading-[1.7] border-b border-[rgba(92,45,27,.06)]">{{ $g['large'] !== null ? rtrim(rtrim(number_format($g['large'], 3), '0'), '.') : '—' }}</td>
                                <td class="px-6 py-4 text-[13px] align-top leading-[1.7] border-b border-[rgba(92,45,27,.06)]">{{ $g['ingredient']->unit ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if (!empty($product->cost_breakdown) && $product->cost_breakdown->isNotEmpty())
                <div class="flex flex-wrap gap-x-6 gap-y-1.5 px-6 py-3 border-t border-line bg-[rgba(92,45,27,.015)] text-[11.5px]">
                    @foreach ($product->cost_breakdown as $sizeRow)
                        @php $marginColor = $sizeRow['margin_pct'] >= 40 ? 'text-green-600' : ($sizeRow['margin_pct'] >= 0 ? 'text-amber-600' : 'text-red-600'); @endphp
                        <div class="flex items-center gap-2.5">
                            <span class="font-bold uppercase tracking-[.04em] opacity-45">{{ ucfirst($sizeRow['size']) }}</span>
                            <span class="opacity-70">Cost <strong class="font-semibold">&#8369;{{ number_format($sizeRow['total_cost'], 2) }}</strong></span>
                            <span class="opacity-70">Profit <strong class="font-semibold {{ $marginColor }}">&#8369;{{ number_format($sizeRow['profit'], 2) }}</strong></span>
                            <span class="opacity-70">Margin <strong class="font-semibold {{ $marginColor }}">{{ $sizeRow['margin_pct'] }}%</strong></span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-3 justify-end px-6 py-3.5 border-t border-line bg-[rgba(92,45,27,.02)]">
                <span class="text-xs font-semibold opacity-60 mr-auto">{{ $product->recipes->count() }} ingredient{{ $product->recipes->count() !== 1 ? 's' : '' }}</span>
                @if ($canSeeCosts)
                    <button class="px-5 py-[7px] bg-accent text-white border-[1.5px] border-accent rounded-lg text-xs font-semibold cursor-pointer font-sans transition-all duration-150 hover:brightness-[.92]" onclick="openProfile({{ $product->id }})">Profile</button>
                    <button class="btn-edit px-5 py-[7px] bg-white text-[#5c2d1b] border-[1.5px] border-line rounded-lg text-xs font-semibold cursor-pointer font-sans transition-all duration-150 hover:bg-[#5c2d1b] hover:text-cream hover:border-[#5c2d1b]" data-product-id="{{ $product->id }}">Edit</button>
                @endif
            </div>
        </div>
        @empty
            <div class="text-center py-10 opacity-35 text-sm">No products with recipes found.</div>
        @endforelse

        {{-- Filter JS --}}
        <script>
        (function () {
            var activeCat = '';
            var search = document.getElementById('recipe-search');
            var pills = document.querySelectorAll('#cat-pills .cat-pill');
            var cards = document.querySelectorAll('.recipe-card');

            function filter() {
                var q = search.value.toLowerCase().trim();
                cards.forEach(function (card) {
                    var catMatch = !activeCat || card.dataset.cat === activeCat;
                    var nameMatch = !q || card.dataset.name.includes(q);
                    card.style.display = catMatch && nameMatch ? '' : 'none';
                });
            }

            pills.forEach(function (pill) {
                pill.addEventListener('click', function () {
                    pills.forEach(function (p) {
                        p.classList.remove('bg-accent', 'text-white', 'border-accent');
                        p.classList.add('bg-white', 'text-[#5c2d1b]', 'border-line');
                    });
                    this.classList.remove('bg-white', 'text-[#5c2d1b]', 'border-line');
                    this.classList.add('bg-accent', 'text-white', 'border-accent');
                    activeCat = this.dataset.cat;
                    filter();
                });
            });

            search.addEventListener('input', filter);
        })();
        </script>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     EDIT MODAL
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="recipe-modal">
    <div class="bg-card rounded-[16px] shadow-[0_8px_40px_rgba(92,45,27,.2)] w-full max-w-[720px] max-h-[90vh] flex flex-col" style="animation:modalIn .2s ease">
        <div class="flex items-center justify-between px-6 py-5 border-b border-line">
            <h2 class="text-[17px] font-extrabold" id="modal-title">Edit Recipe</h2>
            <button class="w-8 h-8 rounded-lg border-none bg-transparent text-[#5c2d1b] cursor-pointer flex items-center justify-center transition-[background] duration-150 hover:bg-[rgba(92,45,27,.08)]" onclick="closeModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6">
            <input type="hidden" id="edit-product-id">

            <div class="form-group">
                <div class="form-label">Product Name</div>
                <input type="text" class="form-input" id="edit-product-name" placeholder="e.g. Classic Milk Tea">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <div class="form-label">Category</div>
                    <input type="text" class="form-input" id="edit-product-category" placeholder="e.g. Milk Tea">
                </div>
                <div class="form-group">
                    <div class="form-label">Price (&#8369;)</div>
                    <input type="number" class="form-input" id="edit-product-price" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>

            {{-- Ingredients --}}
            <div class="mt-5">
                <div class="form-label mb-2">Ingredients</div>

                <div class="grid grid-cols-[2fr_1fr_1fr_80px_36px] gap-2 items-center mb-1.5 max-[900px]:hidden">
                    <span class="text-[10px] font-bold uppercase tracking-[.05em] opacity-40">Ingredient</span>
                    <span class="text-[10px] font-bold uppercase tracking-[.05em] opacity-40">Regular Amt</span>
                    <span class="text-[10px] font-bold uppercase tracking-[.05em] opacity-40">Large Amt</span>
                    <span class="text-[10px] font-bold uppercase tracking-[.05em] opacity-40">Unit</span>
                    <span></span>
                </div>

                <div id="ingredient-list"></div>

                <button class="inline-flex items-center gap-1.5 px-4 py-2 mt-1 bg-cream border-[1.5px] border-dashed border-line rounded-[10px] text-xs font-semibold text-[#5c2d1b] cursor-pointer font-sans transition-all duration-150 hover:border-accent hover:text-accent hover:bg-[rgba(188,97,75,.06)]" onclick="addIngredientRow()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Ingredient
                </button>
            </div>

            {{-- Procedure --}}
            <div class="mt-5 pt-5 border-t border-line">
                <div class="form-label">Preparation Procedure</div>
                <textarea class="form-input" id="edit-product-procedure" placeholder="Step-by-step preparation instructions…"></textarea>
            </div>

            <div class="hidden text-xs font-semibold px-3.5 py-2 rounded-lg mt-3" id="save-status"></div>
        </div>

        <div class="flex items-center gap-2.5 justify-end px-6 py-4 border-t border-line bg-[rgba(92,45,27,.02)]">
            <button class="px-4 py-[9px] mr-auto bg-[#fef2f2] text-[#991b1b] border-[1.5px] border-[#fecaca] rounded-[10px] text-xs font-semibold cursor-pointer font-sans transition-all duration-150 hover:bg-[#991b1b] hover:text-white hover:border-[#991b1b]" id="btn-delete-product" onclick="deleteProduct()">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
                Delete
            </button>
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-save" id="btn-save" onclick="saveRecipe()">Save Changes</button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     INGREDIENT PROFILE DRILL-DOWN MODAL
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="profile-modal">
    <div class="bg-card rounded-[16px] shadow-[0_8px_40px_rgba(92,45,27,.2)] w-full max-w-[720px] max-h-[90vh] flex flex-col border-t-[3px] border-t-accent overflow-hidden" style="animation:modalIn .2s ease">
        <div class="flex items-center gap-3 px-6 py-5 border-b border-line">
            <div class="w-9 h-9 rounded-full bg-accent/10 text-accent flex items-center justify-center shrink-0">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[10px] font-bold uppercase tracking-[.05em] text-accent">Ingredient Profile</div>
                <h2 class="text-[17px] font-extrabold truncate" id="profile-title">&nbsp;</h2>
            </div>
            <button class="w-8 h-8 rounded-lg border-none bg-transparent text-[#5c2d1b] cursor-pointer flex items-center justify-center transition-[background] duration-150 hover:bg-[rgba(92,45,27,.08)] shrink-0" onclick="closeProfile()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6" id="profile-body">
            <div class="text-center py-10 opacity-40 text-[13px]">Loading…</div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     INGREDIENT TEMPLATE (hidden, cloned by JS)
     ═══════════════════════════════════════════════════════════════════ --}}
<template id="ingredient-row-template">
    <div class="grid grid-cols-[2fr_1fr_1fr_80px_36px] gap-2 items-center mb-2 max-[900px]:grid-cols-[1fr_1fr]" data-ingredient-row>
        <select class="form-input !h-[38px]">
            <option value="">Select ingredient…</option>
            @foreach ($allIngredients as $ing)
                <option value="{{ $ing->id }}" data-unit="{{ $ing->unit }}">{{ $ing->name }}</option>
            @endforeach
        </select>
        <input type="number" class="form-input !h-[38px]" step="0.001" min="0.001" placeholder="Regular qty" data-size="regular">
        <input type="number" class="form-input !h-[38px]" step="0.001" min="0.001" placeholder="Large qty" data-size="large">
        <span class="text-[11px] font-semibold opacity-50 text-center" data-unit-display></span>
        <button class="w-9 h-9 rounded-lg border-[1.5px] border-[#fecaca] bg-[#fef2f2] text-[#991b1b] cursor-pointer flex items-center justify-center transition-all duration-150 shrink-0 hover:bg-[#991b1b] hover:text-white hover:border-[#991b1b]" onclick="removeIngredientRow(this)" title="Remove ingredient">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
</template>

{{-- ═══════════════════════════════════════════════════════════════════
     RECIPE DATA (page-embedded JSON)
     ═══════════════════════════════════════════════════════════════════ --}}
<script>
// ── All ingredients lookup ──
var ALL_INGREDIENTS = @json($allIngredients->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit]));

// ── CSRF token ──
var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content
    || document.querySelector('input[name="_token"]')?.value
    || '{{ csrf_token() }}';

// ── Modal state ──
var _editingProductId = null;

// ── Open modal ──
function openModal(productId) {
    _editingProductId = productId;
    document.getElementById('recipe-modal').classList.add('is-open');
    document.getElementById('modal-title').textContent = 'Loading…';
    document.getElementById('btn-save').disabled = true;
    fetchProductData(productId);
}

// ── Fetch product data via API ──
function fetchProductData(productId) {
    fetch('/business/recipes/product/' + productId + '/data', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        populateModal(data);
    })
    .catch(function () {
        populateFromCard(productId);
    });
}

// ── Populate from server-rendered page data ──
function populateFromCard(productId) {
    var product = PRODUCTS_DATA.find(function (p) { return p.id == productId; });
    if (product) {
        populateModal(product);
    } else {
        document.getElementById('modal-title').textContent = 'Edit Recipe';
        document.getElementById('btn-save').disabled = false;
    }
}

// ── Populate modal form ──
function populateModal(data) {
    document.getElementById('edit-product-id').value = data.id;
    document.getElementById('edit-product-name').value = data.name || '';
    document.getElementById('edit-product-category').value = data.category || '';
    document.getElementById('edit-product-price').value = data.price || 0;
    document.getElementById('edit-product-procedure').value = data.procedure || '';
    document.getElementById('modal-title').textContent = 'Edit: ' + (data.name || 'Recipe');

    var list = document.getElementById('ingredient-list');
    list.innerHTML = '';

    if (data.recipes && data.recipes.length > 0) {
        var grouped = {};
        data.recipes.forEach(function (r) {
            var ingId = r.ingredient_id;
            if (!grouped[ingId]) {
                grouped[ingId] = {
                    ingredient_id: ingId,
                    ingredient: r.ingredient,
                    regular_id: null,
                    large_id: null,
                    regular: null,
                    large: null
                };
            }
            if (r.size === 'regular') {
                grouped[ingId].regular_id = r.id;
                grouped[ingId].regular = r.quantity_required;
            } else {
                grouped[ingId].large_id = r.id;
                grouped[ingId].large = r.quantity_required;
            }
        });

        Object.keys(grouped).forEach(function (ingId) {
            addIngredientRow(grouped[ingId]);
        });
    }

    document.getElementById('btn-save').disabled = false;
}

// ── Add ingredient row ──
function addIngredientRow(data) {
    data = data || {};
    var template = document.getElementById('ingredient-row-template');
    var clone = template.content.cloneNode(true);
    var row = clone.querySelector('[data-ingredient-row]');

    var select = row.querySelector('select');
    var regInput = row.querySelector('[data-size="regular"]');
    var lrgInput = row.querySelector('[data-size="large"]');
    var unitDisplay = row.querySelector('[data-unit-display]');

    if (data.ingredient_id) {
        select.value = data.ingredient_id;
        var selected = select.options[select.selectedIndex];
        if (selected) {
            unitDisplay.textContent = selected.dataset.unit || '';
        }
    }

    if (data.regular) regInput.value = data.regular;
    if (data.large) lrgInput.value = data.large;

    if (data.regular_id) row.dataset.regularId = data.regular_id;
    if (data.large_id) row.dataset.largeId = data.large_id;

    select.addEventListener('change', function () {
        var opt = select.options[select.selectedIndex];
        unitDisplay.textContent = opt ? (opt.dataset.unit || '') : '';
    });

    document.getElementById('ingredient-list').appendChild(clone);
}

// ── Remove ingredient row ──
function removeIngredientRow(btn) {
    var row = btn.closest('[data-ingredient-row]');
    if (row) row.remove();
}

// ── Save recipe ──
function saveRecipe() {
    var name = document.getElementById('edit-product-name').value.trim();
    if (!name) {
        alert('Please enter a product name.');
        document.getElementById('edit-product-name').focus();
        return;
    }

    var btn = document.getElementById('btn-save');
    btn.disabled = true;
    var status = document.getElementById('save-status');
    status.className = 'text-xs font-semibold px-3.5 py-2 rounded-lg mt-3 inline-block';
    status.textContent = 'Saving…';

    var productId = document.getElementById('edit-product-id').value;
    if (!productId) return;

    var productData = {
        name: document.getElementById('edit-product-name').value,
        category: document.getElementById('edit-product-category').value,
        price: parseFloat(document.getElementById('edit-product-price').value) || 0,
        procedure: document.getElementById('edit-product-procedure').value,
    };

    var requests = [];

    requests.push(
        fetch('/business/recipes/product/' + productId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(productData)
        })
    );

    var rows = document.querySelectorAll('#ingredient-list [data-ingredient-row]');
    var addedIngredients = {};

    rows.forEach(function (row) {
        var select = row.querySelector('select');
        var ingredientId = select.value;
        if (!ingredientId) return;

        var regularQty = parseFloat(row.querySelector('[data-size="regular"]').value);
        var largeQty = parseFloat(row.querySelector('[data-size="large"]').value);
        var regularId = row.dataset.regularId;
        var largeId = row.dataset.largeId;

        if (regularQty && regularQty > 0) {
            var key = ingredientId + '-regular';
            if (!addedIngredients[key]) {
                addedIngredients[key] = true;
                if (regularId) {
                    requests.push(
                        fetch('/business/recipes/ingredient/' + regularId, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN },
                            body: JSON.stringify({ quantity_required: regularQty })
                        })
                    );
                } else {
                    requests.push(
                        fetch('/business/recipes/product/' + productId + '/ingredient', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN },
                            body: JSON.stringify({ ingredient_id: ingredientId, size: 'regular', quantity_required: regularQty })
                        })
                    );
                }
            }
        }

        if (largeQty && largeQty > 0) {
            var key = ingredientId + '-large';
            if (!addedIngredients[key]) {
                addedIngredients[key] = true;
                if (largeId) {
                    requests.push(
                        fetch('/business/recipes/ingredient/' + largeId, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN },
                            body: JSON.stringify({ quantity_required: largeQty })
                        })
                    );
                } else {
                    requests.push(
                        fetch('/business/recipes/product/' + productId + '/ingredient', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN },
                            body: JSON.stringify({ ingredient_id: ingredientId, size: 'large', quantity_required: largeQty })
                        })
                    );
                }
            }
        }
    });

    Promise.all(requests)
    .then(function (responses) {
        var allOk = responses.every(function (r) { return r.ok; });
        if (allOk) {
            status.className = 'text-xs font-semibold px-3.5 py-2 rounded-lg mt-3 inline-block bg-green-50 text-green-800 border border-green-200';
            status.textContent = 'Saved successfully!';
            setTimeout(function () {
                closeModal();
                refreshPage();
            }, 800);
        } else {
            status.className = 'text-xs font-semibold px-3.5 py-2 rounded-lg mt-3 inline-block bg-red-50 text-red-800 border border-red-200';
            status.textContent = 'Something went wrong. Check your inputs and try again.';
            btn.disabled = false;
        }
    })
    .catch(function () {
        status.className = 'text-xs font-semibold px-3.5 py-2 rounded-lg mt-3 inline-block bg-red-50 text-red-800 border border-red-200';
        status.textContent = 'Network error. Please try again.';
        btn.disabled = false;
    });
}

// ── Delete product ──
function deleteProduct() {
    if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) return;

    var productId = document.getElementById('edit-product-id').value;
    if (!productId) return;

    var btn = document.getElementById('btn-delete-product');
    btn.disabled = true;

    fetch('/business/recipes/product/' + productId + '/delete', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(function (r) { return r.json(); })
    .then(function () {
        closeModal();
        refreshPage();
    })
    .catch(function () {
        btn.disabled = false;
        alert('Failed to delete product.');
    });
}

// ── Close modal ──
function closeModal() {
    document.getElementById('recipe-modal').classList.remove('is-open');
    _editingProductId = null;
}

// ── Refresh page ──
function refreshPage() {
    window.location.reload();
}

// ── Close on overlay click ──
document.addEventListener('click', function (e) {
    var overlay = document.getElementById('recipe-modal');
    if (e.target === overlay) closeModal();
});

// ── Escape key closes modal ──
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
});

// ── Wire up all Edit buttons ──
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var productId = this.dataset.productId;
            if (productId) openModal(productId);
        });
    });
});

// ── Embed product data from server-side render ──
var PRODUCTS_DATA = @json($productsData);

// ═══════════════════════════════════════════════════════════════════
// INGREDIENT PROFILE DRILL-DOWN
// ═══════════════════════════════════════════════════════════════════
function openProfile(productId) {
    var modal = document.getElementById('profile-modal');
    var body  = document.getElementById('profile-body');

    document.getElementById('profile-title').textContent = 'Ingredient Profile';
    body.innerHTML = '<div class="text-center py-10 opacity-40 text-[13px]">Loading…</div>';
    modal.classList.add('is-open');

    fetch('/business/recipes/product/' + productId + '/profile', {
        headers: { 'Accept': 'application/json' }
    })
    .then(function (res) {
        if (!res.ok) throw new Error('Failed to load profile (' + res.status + ')');
        return res.json();
    })
    .then(renderProfile)
    .catch(function (err) {
        body.innerHTML = '<div class="text-center py-10 opacity-50 text-[13px]">'
            + escapeHtml(err.message) + '</div>';
    });
}

function closeProfile() {
    document.getElementById('profile-modal').classList.remove('is-open');
}

function renderProfile(data) {
    var p = data.product;
    document.getElementById('profile-title').textContent = p.name;

    var html = '';

    data.sizes.forEach(function (s) {
        var valueColor = s.margin_pct >= 60 ? '#00b894'
                        : s.margin_pct >= 40 ? '#e17055'
                        : '#d63031';

        html += '<div class="section-label mb-2">' + escapeHtml(s.size) + '</div>'
             +  '<div class="grid grid-cols-3 gap-3 p-[18px] mb-5 bg-accent-light border border-line border-t-[3px] rounded-xl" style="border-top-color:' + valueColor + '">'
             +    '<div class="text-center">'
             +      '<div class="text-xl font-extrabold" style="color:' + valueColor + '">' + peso(s.price) + '</div>'
             +      '<div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-55 mt-[3px]">Selling Price</div>'
             +    '</div>'
             +    '<div class="text-center">'
             +      '<div class="text-xl font-extrabold" style="color:' + valueColor + '">' + peso(s.total_cost) + '</div>'
             +      '<div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-55 mt-[3px]">Ingredient Cost</div>'
             +    '</div>'
             +    '<div class="text-center">'
             +      '<div class="text-xl font-extrabold" style="color:' + valueColor + '">' + s.margin_pct + '%</div>'
             +      '<div class="text-[10px] font-bold uppercase tracking-[.05em] opacity-55 mt-[3px]">Gross Margin</div>'
             +    '</div>'
             +  '</div>';
    });

    if (!data.ingredients.length) {
        html += '<div class="text-center py-[30px] opacity-40 text-[13px]">'
             +  'No recipe ingredients defined yet.</div>';
    } else {
        html += '<table class="summary-table"><thead><tr>'
             +  '<th>Ingredient</th><th>Size</th><th style="text-align:right">Qty</th>'
             +  '<th style="text-align:right">Unit Cost</th><th style="text-align:right">Line Cost</th>'
             +  '<th>Primary Supplier</th>'
             +  '</tr></thead><tbody>';

        data.ingredients.forEach(function (ing) {
            var supplier = ing.supplier
                ? '<div class="flex items-center gap-1.5">'
                +   '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-accent shrink-0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'
                +   '<div class="text-xs">'
                +     '<div class="font-semibold">' + escapeHtml(ing.supplier.name) + '</div>'
                +     (ing.supplier.contact_number
                          ? '<div class="opacity-50 text-[11px]">' + escapeHtml(ing.supplier.contact_number) + '</div>'
                          : '')
                +   '</div>'
                + '</div>'
                : '<span class="text-xs opacity-40 italic">Not linked</span>';

            html += '<tr>'
                 +  '<td><strong>' + escapeHtml(ing.name) + '</strong></td>'
                 +  '<td>' + escapeHtml(ing.size) + '</td>'
                 +  '<td class="num">' + trimNum(ing.quantity_required) + ' ' + escapeHtml(ing.unit) + '</td>'
                 +  '<td class="num">' + peso(ing.unit_cost) + '</td>'
                 +  '<td class="num"><strong>' + peso(ing.line_cost) + '</strong></td>'
                 +  '<td>' + supplier + '</td>'
                 +  '</tr>';
        });

        html += '</tbody></table>';
    }

    var hints = data.sizes.filter(function (s) { return s.suggested_price_65 > 0; });
    if (hints.length) {
        html += '<div class="mt-4 flex items-center gap-2 p-3 px-3.5 rounded-lg bg-[rgba(0,184,148,.07)] border border-[rgba(0,184,148,.2)]">'
             +  '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#00b894" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>'
             +  '<span class="text-xs font-semibold text-[#00b894]">Suggested price at 65% margin — '
             +  hints.map(function (s) {
                    return escapeHtml(s.size) + ': ' + peso(s.suggested_price_65);
                }).join(' · ')
             +  '</span></div>';
    }

    document.getElementById('profile-body').innerHTML = html;
}

function peso(n) {
    return '₱' + Number(n).toLocaleString('en-PH', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });
}

function trimNum(n) {
    return parseFloat(Number(n).toFixed(3)).toString();
}

function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = s == null ? '' : String(s);
    return div.innerHTML;
}

document.getElementById('profile-modal').addEventListener('click', function (e) {
    if (e.target === this) closeProfile();
});
</script>

@endsection
