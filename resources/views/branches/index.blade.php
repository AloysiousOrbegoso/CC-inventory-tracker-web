@extends('layouts.sidebar')

@section('title', 'Businesses')

@section('styles')
    .business-tabs {
        display: flex; gap: 5px; margin-bottom: 24px; flex-wrap: wrap;
        align-items: center; border-bottom: 1px solid var(--border);
    }

    .business-tab {
        padding: 10px 14px; font-size: 12px; font-weight: 600; cursor: pointer;
        border: none; border-bottom: 2px solid transparent; background: transparent;
        color: var(--text); opacity: 0.5; transition: all .15s ease;
        display: flex; align-items: center; gap: 6px;
    }
    .business-tab:hover { opacity: 0.8; }
    .business-tab.active { opacity: 1; border-bottom-color: var(--accent); }

    .add-business-btn {
        margin-left: auto; padding: 8px 14px; border-radius: 999px; font-size: 12px;
        font-weight: 600; cursor: pointer; border: 1.5px solid var(--accent);
        background: var(--accent); color: #fff; display: flex; align-items: center;
        gap: 6px; transition: all .15s ease;
    }
    .add-business-btn:hover { opacity: .9; }

    .business-content { display: grid; grid-template-columns: 1fr 1.3fr; gap: 24px; }
    @media (max-width: 1100px) { .business-content { grid-template-columns: 1fr; } }

    .business-info {
        background: #fff; border: 1.5px solid var(--border);
        border-radius: 16px; padding: 28px;
    }
    .business-info__header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
    .business-info__icon {
        width: 36px; height: 36px; border-radius: 8px; background: var(--accent);
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 700; flex-shrink: 0;
    }
    .business-info__name { font-size: 18px; font-weight: 700; color: var(--text); }
    .business-info__details {
        border: 1px dashed var(--accent); border-radius: 12px; padding: 20px;
        font-size: 13px; line-height: 1.7; color: var(--text);
    }
    .business-info__details p { margin-bottom: 10px; }
    .business-info__details strong { color: var(--accent); font-weight: 600; }
    .business-info__details ul { margin-left: 20px; margin-top: 6px; }
    .business-info__details li { margin-bottom: 4px; }
    .business-info__actions { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }

    .btn-outline {
        padding: 10px 24px; border-radius: 8px; font-size: 13px; font-weight: 600;
        cursor: pointer; border: 1px solid var(--border); background: #fff;
        color: var(--text); transition: all .15s ease;
    }
    .btn-outline:hover { background: var(--bg); }

    .btn-danger {
        padding: 10px 24px; border-radius: 8px; font-size: 13px; font-weight: 600;
        cursor: pointer; border: none; background: #dc2626; color: #fff;
        transition: all .15s ease;
    }
    .btn-danger:hover { background: #b91c1c; }

    .recipes-panel {
        background: #fff; border: 1.5px solid var(--border);
        border-radius: 16px; padding: 28px;
    }
    .recipes-panel__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .recipes-panel__title { font-size: 18px; font-weight: 700; color: var(--text); }

    .recipes-search {
        display: flex; align-items: center; gap: 8px; padding: 8px 14px;
        border: 1px solid var(--border); border-radius: 8px; background: var(--bg);
    }
    .recipes-search input { border: none; background: transparent; outline: none; font-size: 12px; font-family: var(--font); width: 180px; }

    .recipes-categories { display: flex; gap: 16px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
    .recipes-category { font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text); opacity: .5; transition: all .15s ease; }
    .recipes-category:hover, .recipes-category.active { opacity: 1; color: var(--accent); }

    .recipe-card { margin-bottom: 24px; }
    .recipe-card__name { font-size: 16px; font-weight: 700; color: var(--accent); margin-bottom: 16px; }
    .recipe-section { margin-bottom: 16px; }
    .recipe-section__badge {
        display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 11px;
        font-weight: 600; background: var(--bg); color: var(--text); margin-bottom: 12px;
    }
    .recipe-ingredients { display: grid; grid-template-columns: auto 1fr; gap: 6px 20px; font-size: 13px; }
    .recipe-ingredients dt { color: var(--accent); font-weight: 500; }
    .recipe-ingredients dd { color: var(--text); }
    .recipe-procedure { font-size: 13px; line-height: 1.7; color: var(--text); }
    .recipe-procedure h4 { font-size: 14px; font-weight: 700; color: var(--accent); margin: 14px 0 6px; }
    .recipe-procedure h4:first-child { margin-top: 0; }
    .recipe-procedure p { margin-bottom: 8px; }

    .recipes-footer { text-align: right; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); }
    .btn-edit-recipe {
        padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 600;
        cursor: pointer; border: 1px solid var(--border); background: #fff;
        color: var(--text); transition: all .15s ease; text-decoration: none; display: inline-block;
    }
    .btn-edit-recipe:hover { background: var(--bg); }

    .empty-state { text-align: center; color: var(--text-3); font-size: 14px; padding: 40px 20px; }

    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-content { background: #fff; border-radius: 16px; padding: 32px; width: 90%; max-width: 650px; max-height: 90vh; overflow-y: auto; }
    .modal-badge { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; background: var(--bg); color: var(--accent); margin-bottom: 16px; }
    .modal-title { font-size: 20px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
    .modal-subtitle { font-size: 13px; color: var(--text-2); margin-bottom: 24px; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full-width { grid-column: span 2; }
    .form-label { font-size: 13px; font-weight: 600; color: var(--accent); }
    .form-input { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: var(--font); transition: border-color .15s ease; }
    .form-input:focus { outline: none; border-color: var(--accent); }
    .form-textarea { min-height: 100px; resize: vertical; }

    .file-upload {
        display: flex; align-items: center; justify-content: space-between; padding: 12px 14px;
        border: 1px solid var(--border); border-radius: 8px; font-size: 12px;
        color: var(--text-2); cursor: pointer; transition: border-color .15s ease;
    }
    .file-upload:hover { border-color: var(--accent); }

    .btn-submit {
        margin-top: 24px; padding: 12px 28px; border-radius: 8px; font-size: 14px;
        font-weight: 600; cursor: pointer; border: none; background: #16a34a; color: #fff;
        transition: background .15s ease;
    }
    .btn-submit:hover { background: #15803d; }

    .flash-success { background: #dcfce7; border: 1px solid #16a34a; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    .flash-error { background: #fee2e2; border: 1px solid #dc2626; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
@endsection

@section('content')
@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="flash-error">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="business-tabs">
    @foreach($branches as $branch)
        <button class="business-tab {{ $loop->first ? 'active' : '' }}"
                data-branch-id="{{ $branch->id }}"
                onclick="switchBranch({{ $branch->id }}, this)">
            {{ $branch->name }}
        </button>
    @endforeach
    <button class="add-business-btn" onclick="openAddBusinessModal()">
        <span>＋</span>
        Add Business
    </button>
</div>

<div class="business-content">
    <div class="business-info" id="businessInfo">
        @if($branches->isEmpty())
            <div class="empty-state">
                <div style="font-size:36px;margin-bottom:12px">🏪</div>
                <div style="font-size:16px;font-weight:700;color:var(--text);margin-bottom:6px">No businesses yet</div>
                <div style="font-size:13px;color:var(--text-2);margin-bottom:16px">Add your first business to start managing branches, recipes, and more.</div>
                <div style="padding:14px;background:var(--bg);border-radius:10px;text-align:left;font-size:12px;color:var(--text-2);max-width:320px;margin:0 auto">
                    <div style="font-weight:600;color:var(--text);margin-bottom:8px">📋 What you'll get:</div>
                    <div style="margin-bottom:4px">• Branch dashboard with real-time stats</div>
                    <div style="margin-bottom:4px">• Recipe management for your menu</div>
                    <div style="margin-bottom:4px">• Employee tracking and attendance</div>
                    <div style="margin-bottom:4px">• Inventory and logistics overview</div>
                    <div>• Map view with geocoded locations</div>
                </div>
                <button class="btn-submit" style="margin-top:16px;width:auto;display:inline-block" onclick="openAddBusinessModal()">+ Add Your First Business</button>
            </div>
        @else
            @php $branch = $branches->first(); @endphp
            <div class="business-info__header">
                <span class="business-info__icon">B</span>
                <span class="business-info__name">{{ $branch->name }}</span>
            </div>
            <div class="business-info__details">
                <p><strong>Location:</strong> {{ $branch->location ?? 'Not specified' }}</p>
                @if($branch->street_address || $branch->city)
                    <p style="margin-left:12px;font-size:12px;color:var(--text-2);margin-top:-6px;">
                        {{ $branch->street_address }}
                        @if($branch->city){{ $branch->street_address ? ', ' : '' }}{{ $branch->city }}@endif
                        @if($branch->province){{ ($branch->street_address || $branch->city) ? ', ' : '' }}{{ $branch->province }}@endif
                        @if($branch->zip_code) {{ $branch->zip_code }}@endif
                    </p>
                @endif
                <p><strong>Date of Operation:</strong> Established {{ $branch->created_at->format('F j, Y') }}</p>
                <p><strong>About:</strong> {{ $branch->description ?? 'A cozy, neighborhood-centric specialty coffee shop dedicated to serving ethically sourced, small-batch roasted coffee.' }}</p>
                @if($branch->phone || $branch->email)
                    <p><strong>Contact:</strong></p>
                    @if($branch->phone)<p style="margin-left:12px;font-size:12px;">📞 {{ $branch->phone }}</p>@endif
                    @if($branch->email)<p style="margin-left:12px;font-size:12px;">✉️ {{ $branch->email }}</p>@endif
                @endif
                <p><strong>Services Offered:</strong></p>
                <ul>
                    <li>Artisanal espresso bar and manual pour-overs.</li>
                    <li>Curated selection of loose-leaf teas and seasonal iced beverages.</li>
                    <li>Light breakfast pastries and grab-and-go snacks.</li>
                    <li>Free high-speed Wi-Fi and comfortable workstation seating.</li>
                </ul>
            </div>
            <div class="business-info__actions">
                <button class="btn-outline" onclick="editDescription()">Edit Description</button>
                <button class="btn-danger" onclick="disownBusiness()">Disown Business</button>
            </div>
        @endif
    </div>

    <div class="recipes-panel">
        <div class="recipes-panel__header">
            <span class="recipes-panel__title">Recipes</span>
            <div class="recipes-search">
                <span>S</span>
                <input type="text" placeholder="Regular Classic Bubble Tea" id="recipeSearchInput">
            </div>
        </div>

        <div class="recipes-categories">
            <span class="recipes-category active" onclick="filterByCategory('all', this)">Drinks</span>
            <span class="recipes-category" onclick="filterByCategory('Goods', this)">Goods</span>
            <span class="recipes-category" onclick="filterByCategory('Sets', this)">Set</span>
        </div>

        <div id="recipesContainer">
            @if(isset($products) && $products->isNotEmpty())
                @foreach($products->take(3) as $product)
                    <div class="recipe-card" data-category="{{ $product->category ?? 'Drinks' }}">
                        <div class="recipe-card__name">{{ $product->name }}</div>
                        <div class="recipe-section">
                            <span class="recipe-section__badge">Ingredients</span>
                            <dl class="recipe-ingredients">
                                @forelse($product->recipes as $recipe)
                                    <dt>{{ $recipe->ingredient->name ?? 'Unknown' }}</dt>
                                    <dd>{{ $recipe->quantity_required ?? '-' }} {{ $recipe->ingredient->unit ?? '' }}</dd>
                                @empty
                                    <dt>No ingredients</dt>
                                    <dd>-</dd>
                                @endforelse
                            </dl>
                        </div>
                        @if($product->procedure)
                            <div class="recipe-section">
                                <span class="recipe-section__badge">Procedure</span>
                                <div class="recipe-procedure">{!! nl2br(e($product->procedure)) !!}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="recipe-card" data-category="Drinks">
                    <div class="recipe-card__name">Regular Classic Bubble Tea</div>
                    <div class="recipe-section">
                        <span class="recipe-section__badge">Ingredients</span>
                        <dl class="recipe-ingredients">
                            <dt>Pearl</dt><dd>20 grams</dd>
                            <dt>Sugar</dt><dd>10 grams</dd>
                            <dt>Milk Tea</dt><dd>10 ounces</dd>
                            <dt>Tea</dt><dd>120 ml</dd>
                            <dt>Ice</dt><dd>30 grams</dd>
                        </dl>
                    </div>
                    <div class="recipe-section">
                        <span class="recipe-section__badge">Procedure</span>
                        <div class="recipe-procedure">
                            <h4>Cook the pearls</h4>
                            <p>Boil 2 cups of water. Add pearls and stir. Boil for 7 minutes. Drain water</p>
                            <h4>Make it sweet</h4>
                            <p>Same pot mix cooked pearls with brown sugar and 1 tbsp water. Simmer on low for 3 minutes until thick and glossy. Remove from heat.</p>
                            <h4>Brew the tea</h4>
                            <p>Steep tea bags in 1 cup of hot water. Remove tea bags and add simple syrup. Let it cool.</p>
                            <h4>Assemble</h4>
                            <p>In cup, add pearls, ice, tea, and milk. Pop the cover and straw. Serve</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="recipes-footer">
            <a href="{{ route('branches') }}" class="btn-edit-recipe">Edit</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="addBusinessModal">
    <div class="modal-content">
        <span class="modal-badge">Add Business</span>
        <h2 class="modal-title">Register a new business</h2>
        <p class="modal-subtitle">Please provide the needed paperwork for the new business.</p>

        <form action="{{ route('branches.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Business Name</label>
                    <input type="text" name="name" class="form-input" placeholder="Enter your business name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Business Description</label>
                    <textarea name="description" class="form-input form-textarea" placeholder="Enter a short description of your business"></textarea>
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label class="form-label" style="margin-bottom:10px;display:block;">📍 Address</label>
                <div class="form-row" style="margin-bottom:0;">
                    <div class="form-group">
                        <label class="form-label" style="font-size:12px;color:var(--text-2);">Street Address</label>
                        <input type="text" name="street_address" class="form-input" placeholder="e.g. 123 Rizal Avenue">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size:12px;color:var(--text-2);">City / Municipality</label>
                        <input type="text" name="city" class="form-input" placeholder="e.g. Makati City">
                    </div>
                </div>
                <div class="form-row" style="margin-bottom:0;">
                    <div class="form-group">
                        <label class="form-label" style="font-size:12px;color:var(--text-2);">Province</label>
                        <input type="text" name="province" class="form-input" placeholder="e.g. Metro Manila">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size:12px;color:var(--text-2);">Zip Code</label>
                        <input type="text" name="zip_code" class="form-input" placeholder="e.g. 1230">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-input" placeholder="e.g. +63 917 123 4567">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="e.g. info@mybusiness.ph">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">DTI Registration</label>
                    <div class="file-upload">
                        <span>Upload a PDF of your DTI Business Name Registration</span>
                        <span class="file-upload__icon">📄</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">SEC Registration</label>
                    <div class="file-upload">
                        <span>Upload a PDF of your Certificate of Registration</span>
                        <span class="file-upload__icon">📄</span>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">BIR Registration</label>
                    <div class="file-upload">
                        <span>Upload a PDF of your Certificate of Registration (COR)</span>
                        <span class="file-upload__icon">📄</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">LGU Permit</label>
                    <div class="file-upload">
                        <span>Upload a PDF of your Mayor's Permit</span>
                        <span class="file-upload__icon">📄</span>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-submit">Add new business</button>
        </form>
    </div>
</div>

<script>
function switchBranch(branchId, el) {
    var url = new URL(window.location.href);
    url.searchParams.set('branch_id', branchId);
    history.pushState({}, '', url.toString());

    document.querySelectorAll('.business-tab').forEach(tab => tab.classList.remove('active'));
    el.classList.add('active');

    document.getElementById('businessInfo').style.opacity = '0.4';
    document.getElementById('recipesContainer').style.opacity = '0.4';

    fetch('/ajax/branches?branch_id=' + branchId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        renderBranchData(data);
        document.getElementById('businessInfo').style.opacity = '1';
        document.getElementById('recipesContainer').style.opacity = '1';
    })
    .catch(function() {
        document.getElementById('businessInfo').style.opacity = '1';
        document.getElementById('recipesContainer').style.opacity = '1';
    });
}

function renderBranchData(data) {
    if (!data.branch) return;
    var branch = data.branch;

    var infoCard = document.getElementById('businessInfo');
    if (infoCard) {
        var html = '<div class="business-info__header">';
        html += '<span class="business-info__icon">B</span>';
        html += '<span class="business-info__name">' + branch.name + '</span>';
        html += '</div>';
        html += '<div class="business-info__details">';
        html += '<p><strong>Location:</strong> ' + (branch.location || 'Not specified') + '</p>';
        if (branch.street_address || branch.city) {
            var addrParts = [];
            if (branch.street_address) addrParts.push(branch.street_address);
            if (branch.city) addrParts.push(branch.city);
            if (branch.province) addrParts.push(branch.province);
            if (branch.zip_code) addrParts.push(branch.zip_code);
            html += '<p style="margin-left:12px;font-size:12px;color:var(--text-2);margin-top:-6px;">' + addrParts.join(', ') + '</p>';
        }
        html += '<p><strong>Date of Operation:</strong> Established ' + new Date(branch.created_at).toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric'}) + '</p>';
        html += '<p><strong>About:</strong> ' + (branch.description || 'A cozy, neighborhood-centric specialty coffee shop.') + '</p>';
        if (branch.phone || branch.email) {
            html += '<p><strong>Contact:</strong></p>';
            if (branch.phone) html += '<p style="margin-left:12px;font-size:12px;">📞 ' + branch.phone + '</p>';
            if (branch.email) html += '<p style="margin-left:12px;font-size:12px;">✉️ ' + branch.email + '</p>';
        }
        html += '<p><strong>Services Offered:</strong></p>';
        html += '<ul>';
        html += '<li>Artisanal espresso bar and manual pour-overs.</li>';
        html += '<li>Curated selection of loose-leaf teas and seasonal iced beverages.</li>';
        html += '<li>Light breakfast pastries and grab-and-go snacks.</li>';
        html += '<li>Free high-speed Wi-Fi and comfortable workstation seating.</li>';
        html += '</ul></div>';
        html += '<div class="business-info__actions">';
        html += '<button class="btn-outline" onclick="editDescription()">Edit Description</button>';
        html += '<button class="btn-danger" onclick="disownBusiness()">Disown Business</button>';
        html += '</div>';
        infoCard.innerHTML = html;
    }

    var recipesContainer = document.getElementById('recipesContainer');
    if (recipesContainer && data.products) {
        if (data.products.length === 0) {
            recipesContainer.innerHTML = '<div class="empty-state">No recipes found.</div>';
        } else {
            var recipesHtml = '';
            data.products.forEach(function(product) {
                recipesHtml += '<div class="recipe-card" data-category="' + (product.category || 'Drinks') + '">';
                recipesHtml += '<div class="recipe-card__name">' + product.name + '</div>';
                if (product.recipes && product.recipes.length > 0) {
                    recipesHtml += '<div class="recipe-section"><span class="recipe-section__badge">Ingredients</span><dl class="recipe-ingredients">';
                    product.recipes.forEach(function(recipe) {
                        recipesHtml += '<dt>' + recipe.ingredient_name + '</dt><dd>' + recipe.quantity + ' ' + recipe.unit + '</dd>';
                    });
                    recipesHtml += '</dl></div>';
                }
                if (product.procedure) {
                    recipesHtml += '<div class="recipe-section"><span class="recipe-section__badge">Procedure</span><div class="recipe-procedure">' + product.procedure.replace(/\n/g, '<br>') + '</div></div>';
                }
                recipesHtml += '</div>';
            });
            recipesContainer.innerHTML = recipesHtml;
        }
    }
}

function openAddBusinessModal() { document.getElementById('addBusinessModal').classList.add('active'); }
function closeAddBusinessModal() { document.getElementById('addBusinessModal').classList.remove('active'); }

document.getElementById('addBusinessModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddBusinessModal();
});
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeAddBusinessModal(); });

function filterByCategory(category, el) {
    document.querySelectorAll('.recipes-category').forEach(cat => cat.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.recipe-card').forEach(card => {
        card.style.display = (category === 'all' || card.dataset.category === category) ? 'block' : 'none';
    });
}

document.getElementById('recipeSearchInput').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    document.querySelectorAll('.recipe-card').forEach(card => {
        const name = card.querySelector('.recipe-card__name').textContent.toLowerCase();
        card.style.display = name.includes(query) ? 'block' : 'none';
    });
});

function editDescription() { console.log('Edit description clicked'); }
function disownBusiness() { if (confirm('Are you sure you want to disown this business?')) { console.log('Disown business clicked'); } }
</script>
@endsection
