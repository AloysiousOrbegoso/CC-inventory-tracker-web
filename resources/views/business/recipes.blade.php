@extends('layouts.sidebar')

@section('title', 'Recipes')

@section('subtitle', 'Product catalog and recipe ingredients.')

@section('content')
    @include('partials._breadcrumbs', ['bc_items' => [
        ['label' => 'Branches', 'url' => route('branches')],
        ['label' => 'Recipes', 'url' => null],
    ]])

    <div class="dash-main">
        @if(isset($branches) && $branches->isNotEmpty())
            <div class="widget" style="margin-bottom:16px;">
                <div class="widget-head">
                    <span class="detail-sub">Switch branch:</span>
                </div>
                <div class="flex gap-2 flex-wrap">
                    @foreach($branches as $b)
                        <a href="{{ url('/branches/'.$b->id.'?tab=recipe') }}" class="badge {{ $b->id == request()->query('branch_id') ? 'green' : 'gray' }}" style="text-decoration:none;">
                            {{ $b->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="widget-head" style="margin-bottom:0;">
            <span class="detail-sub">Global catalog &mdash; recipes apply to all branches.</span>
        </div>

        @forelse ($products as $product)
            @php
                $regularRecipes = $product->recipes->where('size', 'regular')->values();
                $largeRecipes = $product->recipes->where('size', 'large')->values();
                $maxRows = max($regularRecipes->count(), $largeRecipes->count());
            @endphp
            <div class="widget" style="margin-bottom:16px;">
                <div class="widget-head">
                    <h2>{{ $product->name }}</h2>
                    <div>
                        @if ($product->category)
                            <span class="badge blue">{{ $product->category }}</span>
                        @endif
                        <span class="badge green">&#8369;{{ number_format($product->price, 2) }}</span>
                    </div>
                </div>

                @if ($product->recipes->isEmpty())
                    <div class="empty-state-icon" style="padding:20px 16px">
                        <div style="font-size:22px;margin-bottom:6px">🧂</div>
                        <div style="font-size:12px;font-weight:600;color:var(--text)">No ingredients assigned</div>
                        <div style="font-size:11px;color:var(--text-3)">Add ingredients from the recipe editor.</div>
                    </div>
                @else
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th colspan="2" style="text-align: center;">Regular</th>
                                <th colspan="2" style="text-align: center;">Large</th>
                            </tr>
                            <tr>
                                <th>Measurement</th>
                                <th>Ingredient</th>
                                <th>Measurement</th>
                                <th>Ingredient</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < $maxRows; $i++)
                                <tr>
                                    @if ($regularRecipes->has($i))
                                        <td>{{ rtrim(rtrim(number_format($regularRecipes[$i]->quantity_required, 3), '0'), '.') }}{{ $regularRecipes[$i]->ingredient->unit ?? '' }}</td>
                                        <td class="cell-primary">{{ $regularRecipes[$i]->ingredient->name ?? '—' }}</td>
                                    @else
                                        <td>&mdash;</td>
                                        <td>&mdash;</td>
                                    @endif
                                    @if ($largeRecipes->has($i))
                                        <td>{{ rtrim(rtrim(number_format($largeRecipes[$i]->quantity_required, 3), '0'), '.') }}{{ $largeRecipes[$i]->ingredient->unit ?? '' }}</td>
                                        <td class="cell-primary">{{ $largeRecipes[$i]->ingredient->name ?? '—' }}</td>
                                    @else
                                        <td>&mdash;</td>
                                        <td>&mdash;</td>
                                    @endif
                                </tr>
                            @endfor
                        </tbody>
                    </table>

                    @if ($largeRecipes->isEmpty())
                        <div class="detail-sub" style="margin-top: 8px;">No Large-size formula configured yet &mdash; only Regular is sold via POS.</div>
                    @endif
                @endif

                @if ($product->procedure)
                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(92, 45, 27, 0.15);">
                        <div class="stat-label" style="margin-bottom: 8px;">Procedure</div>
                        @foreach (explode("\n", $product->procedure) as $line)
                            @continue(trim($line) === '')
                            <p style="font-size: 13px; margin-bottom: 6px;">{{ $line }}</p>
                        @endforeach
                    </div>
                @endif

                @if (isset($canSeeCosts) && $canSeeCosts && isset($product->cost_breakdown) && $product->cost_breakdown->isNotEmpty())
                    <div style="margin-top: 12px; padding: 10px 12px; background: rgba(0,0,0,0.03); border-radius: 6px;">
                        <div style="font-size: 11px; font-weight: 600; color: var(--text-3); margin-bottom: 6px;">Cost Breakdown</div>
                        <table class="data-table" style="font-size: 12px;">
                            <thead>
                                <tr>
                                    <th>Size</th>
                                    <th>Cost</th>
                                    <th>Price</th>
                                    <th>Profit</th>
                                    <th>Margin %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($product->cost_breakdown as $sizeData)
                                    <tr>
                                        <td>{{ ucfirst($sizeData['size']) }}</td>
                                        <td>&#8369;{{ number_format($sizeData['total_cost'], 2) }}</td>
                                        <td>&#8369;{{ number_format($sizeData['price'], 2) }}</td>
                                        <td>&#8369;{{ number_format($sizeData['profit'], 2) }}</td>
                                        <td>{{ $sizeData['margin_pct'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button class="btn-sm outline" style="margin-top: 8px;" onclick="openIngredientProfileModal({{ $product->id }})">
                            &#128202; View Ingredient Profile
                        </button>
                    </div>
                @endif

                @if (isset($canSeeCosts) && $canSeeCosts)
                    <div style="margin-top: 10px; display: flex; gap: 6px; flex-wrap: wrap;">
                        <button class="btn-sm outline" onclick="openEditProductModal({{ $product->id }})">&#9998; Edit Product</button>
                        <button class="btn-sm danger" onclick="deleteProduct({{ $product->id }}, '{{ addslashes($product->name) }}')">&#10005; Delete</button>
                    </div>
                @endif
            </div>
        @empty
            <div class="widget">
                <div class="empty-state-icon" style="padding:24px 16px">
                    <div style="font-size:24px;margin-bottom:6px">📖</div>
                    <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px">No products yet</div>
                    <div style="font-size:11px;color:var(--text-3)">Add products and their recipes to build your menu catalog.</div>
                </div>
            </div>
        @endforelse

        @if (isset($canSeeCosts) && $canSeeCosts && request()->ajax() === false)
            <div class="widget" style="margin-top: 16px;">
                <div class="widget-head">
                    <h2>&#128221; Recipe Editor</h2>
                </div>
                @include('business.recipe-editor')
            </div>
        @endif
    </div>
@endsection
