@extends('layouts.sidebar')

@section('title', 'Map')

@section('styles')
<style>
    .map-container {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 20px;
        height: calc(100vh - 140px);
    }

    @media (max-width: 1100px) {
        .map-container {
            grid-template-columns: 1fr;
            height: auto;
        }
        .map-sidebar { order: -1; }
    }

    /* ══ MAP ═══════════════════════════════════════════════════════════ */
    #map {
        width: 100%;
        height: 100%;
        min-height: 500px;
        border-radius: 16px;
        border: 1.5px solid var(--border);
    }

    /* ══ SIDEBAR ═════════════════════════════════════════════════════ */
    .map-sidebar {
        display: flex;
        flex-direction: column;
        gap: 16px;
        overflow-y: auto;
        max-height: calc(100vh - 140px);
    }

    .map-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .stat-card {
        background: var(--card);
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
    }

    .stat-card__label {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-3);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    .stat-card__value {
        font-size: 22px;
        font-weight: 800;
        color: var(--text);
    }

    .stat-card__value--accent {
        color: var(--accent);
    }

    /* ══ BRANCH CARDS ════════════════════════════════════════════════ */
    .branch-list-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .branch-list-title {
        font-size: 15px;
        font-weight: 800;
        color: var(--text);
    }

    .branch-card {
        background: var(--card);
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        cursor: pointer;
        transition: all .15s ease;
        margin-bottom: 10px;
    }

    .branch-card:hover {
        border-color: var(--accent);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,.06);
    }

    .branch-card.active {
        border-color: var(--accent);
        background: var(--color-accent-light);
    }

    .branch-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .branch-card__name {
        font-size: 14px;
        font-weight: 700;
        color: var(--text);
    }

    .branch-card__status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 99px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .branch-card__status--active {
        background: rgba(22, 163, 74, 0.12);
        color: #16a34a;
    }

    .branch-card__status--inactive {
        background: rgba(220, 38, 38, 0.12);
        color: #dc2626;
    }

    .branch-card__location {
        font-size: 12px;
        color: var(--text-3);
        margin-bottom: 10px;
    }

    .branch-card__metrics {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
    }

    .metric { text-align: center; }

    .metric__value {
        font-size: 16px;
        font-weight: 800;
        color: var(--text);
    }

    .metric__value--danger { color: #dc2626; }
    .metric__value--warning { color: #f59e0b; }

    .metric__label {
        font-size: 10px;
        color: var(--text-3);
        font-weight: 600;
    }

    /* ══ CUSTOM INFO WINDOW ════════════════════════════════════════ */
    .gm-style-iw {
        border-radius: 12px !important;
        border: 1.5px solid var(--border) !important;
        padding: 0 !important;
    }

    .popup-name {
        font-size: 15px;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 4px;
    }

    .popup-location {
        font-size: 12px;
        color: var(--text-3);
        margin-bottom: 10px;
    }

    .popup-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .popup-stat {
        text-align: center;
        padding: 8px;
        background: var(--bg);
        border-radius: 8px;
    }

    .popup-stat__value {
        font-size: 16px;
        font-weight: 800;
        color: var(--text);
    }

    .popup-stat__label {
        font-size: 10px;
        color: var(--text-3);
        font-weight: 600;
    }

    .popup-btn {
        display: block;
        width: 100%;
        margin-top: 10px;
        padding: 8px;
        border-radius: 8px;
        border: none;
        background: var(--accent);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
    }

    .popup-btn:hover { opacity: 0.9; }

    .legend {
        background: var(--card);
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 14px 16px;
    }

    .legend__title {
        font-size: 13px;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 10px;
    }

    .legend__item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--text-2);
        margin-bottom: 6px;
    }

    .legend__dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .legend__dot--green { background: #16a34a; }
    .legend__dot--yellow { background: #f59e0b; }
    .legend__dot--red { background: #dc2626; }
</style>
@endsection

@section('content')
<div class="mb-4">
    <div class="text-[22px] font-extrabold tracking-tight">Live Map</div>
    <div class="text-[13px] text-ink-2 mt-0.5">Pinpoint your branch locations and track ingredient supply status</div>
</div>

<div class="map-container">
    {{-- LEFT: Map --}}
    <div id="map"></div>

    {{-- RIGHT: Sidebar --}}
    <div class="map-sidebar">
        {{-- Summary Stats --}}
        <div class="map-stats">
            <div class="stat-card">
                <div class="stat-card__label">Total Branches</div>
                <div class="stat-card__value">{{ $branches->count() }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">Active</div>
                <div class="stat-card__value stat-card__value--accent">{{ $branches->where('status', 'active')->count() }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">Pending Alerts</div>
                <div class="stat-card__value" style="color: {{ $branches->sum('pending_alerts') > 0 ? '#dc2626' : '#16a34a' }}">
                    {{ $branches->sum('pending_alerts') }}
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__label">Low Stock Items</div>
                <div class="stat-card__value" style="color: {{ $branches->sum('low_stock_count') > 0 ? '#f59e0b' : '#16a34a' }}">
                    {{ $branches->sum('low_stock_count') }}
                </div>
            </div>
        </div>

        {{-- Branch List --}}
        <div>
            <div class="branch-list-header">
                <span class="branch-list-title">Branches</span>
            </div>

            @foreach($branches as $branch)
                <div class="branch-card" id="branch-card-{{ $branch['id'] }}" onclick="focusBranch({{ $branch['id'] }})">
                    <div class="branch-card__header">
                        <span class="branch-card__name">{{ $branch['name'] }}</span>
                        <span class="branch-card__status branch-card__status--{{ $branch['status'] }}">
                            {{ $branch['status'] }}
                        </span>
                    </div>
                    <div class="branch-card__location">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -1px; margin-right: 3px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        {{ $branch['location'] ?? 'No location set' }}
                    </div>
                    <div class="branch-card__metrics">
                        <div class="metric">
                            <div class="metric__value {{ $branch['pending_alerts'] > 0 ? 'metric__value--danger' : '' }}">{{ $branch['pending_alerts'] }}</div>
                            <div class="metric__label">Alerts</div>
                        </div>
                        <div class="metric">
                            <div class="metric__value {{ $branch['low_stock_count'] > 0 ? 'metric__value--warning' : '' }}">{{ $branch['low_stock_count'] }}</div>
                            <div class="metric__label">Low Stock</div>
                        </div>
                        <div class="metric">
                            <div class="metric__value">{{ $branch['staff_count'] }}</div>
                            <div class="metric__label">Staff</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Map Layers --}}
        <div class="card p-4">
            <div class="text-[13px] font-extrabold mb-3">Map Layers</div>
            <label class="flex items-center gap-3 py-2 cursor-pointer">
                <input type="checkbox" checked id="layerBranches" onchange="toggleLayer('branches')" class="accent-[var(--accent)]">
                <span class="text-[12px] font-semibold">Branch Locations</span>
                <span class="badge-gray ml-auto">{{ $branches->count() }}</span>
            </label>
            <label class="flex items-center gap-3 py-2 cursor-pointer">
                <input type="checkbox" id="layerSuppliers" onchange="toggleLayer('suppliers')" class="accent-[var(--accent)]">
                <span class="text-[12px] font-semibold">Suppliers</span>
                <span class="badge-gray ml-auto">{{ $suppliers->count() }}</span>
            </label>
            <label class="flex items-center gap-3 py-2 cursor-pointer">
                <input type="checkbox" id="layerWarehouses" onchange="toggleLayer('warehouses')" class="accent-[var(--accent)]">
                <span class="text-[12px] font-semibold">Warehouses</span>
                <span class="badge-gray ml-auto">Coming Soon</span>
            </label>
        </div>

        {{-- Auto-Geocode --}}
        <div class="card p-4">
            <div class="text-[13px] font-extrabold mb-2">Auto-Geocode</div>
            <div class="text-[11px] text-ink-3 mb-3">Set coordinates from address text using OpenStreetMap Nominatim.</div>
            <button type="button" id="geocodeBranchesBtn" class="w-full py-2.5 rounded-lg border-[1.5px] border-[rgba(188,97,75,.3)] bg-[rgba(188,97,75,.04)] text-[12px] font-semibold text-accent cursor-pointer hover:bg-[rgba(188,97,75,.08)] transition-colors" onclick="geocodeBranches()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align: -2px; margin-right: 4px;">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
                Geocode All Branches
            </button>
            <button type="button" id="geocodeSuppliersBtn" class="w-full mt-2 py-2.5 rounded-lg border-[1.5px] border-[rgba(99,102,241,.3)] bg-[rgba(99,102,241,.04)] text-[12px] font-semibold text-[#6366f1] cursor-pointer hover:bg-[rgba(99,102,241,.08)] transition-colors" onclick="geocodeSuppliers()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align: -2px; margin-right: 4px;">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
                Geocode All Suppliers
            </button>
            <div id="geocodeStatus" class="text-[11px] text-ink-3 mt-2" style="display:none;"></div>
        </div>

        {{-- Legend --}}
        <div class="legend">
            <div class="legend__title">Marker Legend</div>
            <div class="legend__item">
                <span class="legend__dot legend__dot--green"></span>
                All OK — no alerts
            </div>
            <div class="legend__item">
                <span class="legend__dot legend__dot--yellow"></span>
                Low stock warnings
            </div>
            <div class="legend__item">
                <span class="legend__dot legend__dot--red"></span>
                Pending alerts
            </div>
            <div class="legend__item" style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border);">
                <span class="legend__dot" style="background: #6366f1;"></span>
                Supplier
            </div>
            <div class="legend__item">
                <span class="legend__dot" style="background: #8b5cf6;"></span>
                Warehouse (coming soon)
            </div>
        </div>
    </div>
</div>

<script>
    var map;
    var branchMarkers = {};
    var supplierMarkers = [];
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function initMap() {
        var branches = @json($branches);
        var suppliers = @json($suppliers);

        map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: 12.8797, lng: 121.7740 },
            zoom: 6,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true,
            styles: [
                { featureType: 'poi', stylers: [{ visibility: 'off' }] }
            ]
        });

        var bounds = new google.maps.LatLngBounds();
        var hasCoords = false;

        // ═══ Branch Markers ═══
        branches.forEach(function(branch) {
            if (!branch.latitude || !branch.longitude) return;
            hasCoords = true;

            var color = branch.pending_alerts > 0 ? '#dc2626' :
                        branch.low_stock_count > 0 ? '#f59e0b' : '#16a34a';
            var initial = branch.name.replace(/Branch\s*/i, '').charAt(0).toUpperCase();

            var marker = new google.maps.Marker({
                position: { lat: parseFloat(branch.latitude), lng: parseFloat(branch.longitude) },
                map: map,
                title: branch.name,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 14,
                    fillColor: color,
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 3,
                    labelOrigin: new google.maps.Point(0, 0)
                },
                label: {
                    text: initial,
                    color: '#fff',
                    fontSize: '14px',
                    fontWeight: '800'
                }
            });

            var content = '<div style="padding:16px;min-width:220px;font-family:var(--font-sans)">' +
                '<div class="popup-name">' + branch.name + '</div>' +
                '<div class="popup-location">' + (branch.location || 'No location') + '</div>' +
                '<div class="popup-stats">' +
                    '<div class="popup-stat"><div class="popup-stat__value">' + branch.today_sales.toLocaleString() + '</div><div class="popup-stat__label">Today Sales</div></div>' +
                    '<div class="popup-stat"><div class="popup-stat__value">' + branch.total_stock_items + '</div><div class="popup-stat__label">Stock Items</div></div>' +
                    '<div class="popup-stat"><div class="popup-stat__value">' + branch.pending_alerts + '</div><div class="popup-stat__label">Alerts</div></div>' +
                    '<div class="popup-stat"><div class="popup-stat__value">' + branch.staff_count + '</div><div class="popup-stat__label">Staff</div></div>' +
                '</div>' +
                '<a href="/branches/' + branch.id + '" class="popup-btn">View Branch →</a>' +
            '</div>';

            var infoWindow = new google.maps.InfoWindow({ content: content });
            marker.addListener('click', function() { infoWindow.open(map, marker); });

            branchMarkers[branch.id] = { marker: marker, infoWindow: infoWindow };
            bounds.extend(marker.getPosition());
        });

        // ═══ Supplier Markers ═══
        suppliers.forEach(function(supplier) {
            if (!supplier.latitude || !supplier.longitude) return;
            hasCoords = true;

            var marker = new google.maps.Marker({
                position: { lat: parseFloat(supplier.latitude), lng: parseFloat(supplier.longitude) },
                map: null,
                title: supplier.name,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 11,
                    fillColor: '#6366f1',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2
                },
                label: {
                    text: 'S',
                    color: '#fff',
                    fontSize: '10px',
                    fontWeight: '800'
                }
            });

            var content = '<div style="padding:16px;min-width:200px;font-family:var(--font-sans)">' +
                '<div class="popup-name" style="color:#6366f1;">' + supplier.name + '</div>' +
                '<div class="popup-location">' + (supplier.address || 'No address') + '</div>' +
                (supplier.contact_person ? '<div style="font-size:12px;margin-bottom:4px;">Contact: ' + supplier.contact_person + '</div>' : '') +
                (supplier.contact_number ? '<div style="font-size:12px;margin-bottom:8px;">Phone: ' + supplier.contact_number + '</div>' : '') +
                '<div class="popup-stats">' +
                    '<div class="popup-stat"><div class="popup-stat__value">' + supplier.ingredient_count + '</div><div class="popup-stat__label">Ingredients</div></div>' +
                '</div>' +
                '<a href="/suppliers/' + supplier.id + '" class="popup-btn" style="background:#6366f1;">View Supplier →</a>' +
            '</div>';

            var infoWindow = new google.maps.InfoWindow({ content: content });
            marker.addListener('click', function() { infoWindow.open(map, marker); });

            supplierMarkers.push({ marker: marker, infoWindow: infoWindow });
            bounds.extend(marker.getPosition());
        });

        if (hasCoords) {
            map.fitBounds(bounds, 50);
        }
    }

    // ═══ Sidebar: Focus Branch ═══
    function focusBranch(branchId) {
        document.querySelectorAll('.branch-card').forEach(function(card) {
            card.classList.remove('active');
        });
        document.getElementById('branch-card-' + branchId).classList.add('active');

        var data = branchMarkers[branchId];            if (data) {
                map.panTo(data.marker.getPosition());
                map.setZoom(14);
                data.infoWindow.open(map, data.marker);
            }
    }

    // ═══ Layer Toggle ═══
    function toggleLayer(layerName) {
        if (layerName === 'branches') {
            var checked = document.getElementById('layerBranches').checked;
            Object.values(branchMarkers).forEach(function(data) {
                data.marker.setMap(checked ? map : null);
            });
        } else if (layerName === 'suppliers') {
            var checked = document.getElementById('layerSuppliers').checked;
            supplierMarkers.forEach(function(data) {
                data.marker.setMap(checked ? map : null);
            });
        }
    }

    // ═══ Geocode Buttons ═══
    function showGeocodeStatus(msg, isError) {
        var el = document.getElementById('geocodeStatus');
        el.style.display = 'block';
        el.style.color = isError ? '#dc2626' : '#16a34a';
        el.textContent = msg;
    }

    async function geocodeBranches() {
        var btn = document.getElementById('geocodeBranchesBtn');
        btn.disabled = true;
        btn.textContent = 'Geocoding...';
        try {
            var res = await fetch('/api/geocode/branches', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            var data = await res.json();
            if (data.success) {
                showGeocodeStatus('Updated ' + data.updated + ' of ' + data.total + ' branches. Reloading...', false);
                setTimeout(function() { window.location.reload(); }, 1500);
            } else {
                showGeocodeStatus(data.message || 'Geocoding failed.', true);
            }
        } catch (e) {
            showGeocodeStatus('Network error. Please try again.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align:-2px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Geocode All Branches';
        }
    }

    async function geocodeSuppliers() {
        var btn = document.getElementById('geocodeSuppliersBtn');
        btn.disabled = true;
        btn.textContent = 'Geocoding...';
        try {
            var res = await fetch('/api/geocode/suppliers', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            var data = await res.json();
            if (data.success) {
                showGeocodeStatus('Updated ' + data.updated + ' of ' + data.total + ' suppliers. Reloading...', false);
                setTimeout(function() { window.location.reload(); }, 1500);
            } else {
                showGeocodeStatus(data.message || 'Geocoding failed.', true);
            }
        } catch (e) {
            showGeocodeStatus('Network error. Please try again.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align:-2px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Geocode All Suppliers';
        }
    }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key', '') }}&callback=initMap"></script>
@endsection
