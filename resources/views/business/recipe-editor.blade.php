@php
    $currentUserId = auth()->id();
    $canEdit = isset($canSeeCosts) && $canSeeCosts;
    $allIngredients = isset($allIngredients) ? $allIngredients : collect();
@endphp

<div class="recipe-editor" style="padding: 12px 16px;">
    <p style="font-size: 12px; color: var(--text-3); margin-bottom: 12px;">
        Select a product below to edit its details, or manage ingredients per size.
    </p>

    @forelse ($products as $product)
        @if ($canEdit)
            <div class="widget" style="margin-bottom: 12px;">
                <div class="widget-head" style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h2>{{ $product->name }}</h2>
                        @if ($product->category)
                            <span class="badge blue">{{ $product->category }}</span>
                        @endif
                        <span class="badge green">&#8369;{{ number_format($product->price, 2) }}</span>
                    </div>
                    <button class="btn-sm outline" onclick="openEditProductModal({{ $product->id }})">&#9998; Edit</button>
                </div>

                @if ($product->recipes->isEmpty())
                    <p style="font-size: 12px; color: var(--text-3); margin: 8px 0;">No ingredients assigned yet.</p>
                @else
                    <table class="data-table" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th>Ingredient</th>
                                <th>Qty ({{ $product->recipes->first()->ingredient->unit ?? 'unit' }})</th>
                                <th>Unit Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($product->recipes as $recipe)
                                @php
                                    $ingredient = $recipe->ingredient;
                                    $primarySupplier = $ingredient?->suppliers->firstWhere('pivot.is_primary', true);
                                    $unitCost = $primarySupplier?->pivot?->unit_cost ?? 0;
                                @endphp
                                <tr>
                                    <td>{{ ucfirst($recipe->size) }}</td>
                                    <td class="cell-primary">{{ $ingredient?->name ?? '—' }}</td>
                                    <td>{{ number_format($recipe->quantity_required, 3) }}</td>
                                    <td>&#8369;{{ number_format($unitCost, 2) }}</td>
                                    <td>
                                        <button class="btn-sm outline" onclick="openUpdateIngredientModal({{ $product->id }}, {{ $recipe->id }})">&#9998;</button>
                                        <button class="btn-sm danger" onclick="removeIngredientFromProduct({{ $product->id }}, {{ $recipe->id }})">&#10005;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;">
                    <button class="btn-sm" onclick="openAddIngredientModal({{ $product->id }})">&#10132; Add Ingredient</button>
                    <button class="btn-sm danger" onclick="deleteProduct({{ $product->id }}, '{{ addslashes($product->name) }}')">&#10005; Delete Product</button>
                </div>
            </div>
        @endif
    @empty
        <div class="empty-state-icon" style="padding: 16px;">
            <div style="font-size: 20px; margin-bottom: 4px;">&#127860;</div>
            <div style="font-size: 12px; font-weight: 600; color: var(--text); margin-bottom: 2px;">No products to manage</div>
            <div style="font-size: 11px; color: var(--text-3);">Create products first, then add their recipes here.</div>
        </div>
    @endforelse
</div>
