@extends('layouts.sidebar')

@section('title', 'Inventory Intelligence')

@section('styles')
    /* ═══ WAREHOUSE MANAGEMENT STYLE ═══ */
    .wh-grid {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 20px;
    }
    @media (max-width: 1024px) { .wh-grid { grid-template-columns: 1fr; } }

    /* Alert strip — critical items that need immediate attention */
    .wh-alert-strip {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        overflow-x: auto;
    }
    .wh-alert-card {
        flex: 1;
        min-width: 160px;
        padding: 14px 16px;
        border-radius: 10px;
        border: 1.5px solid;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        cursor: pointer;
        transition: all .15s;
    }
    .wh-alert-card:hover { transform: translateY(-1px); }
    .wh-alert-card.critical { background: #fef2f2; border-color: #fecaca; }
    .wh-alert-card.warning { background: #fffbeb; border-color: #fde68a; }
    .wh-alert-card.out { background: #f3f4f6; border-color: #e5e7eb; }
    .wh-alert-icon { font-size: 20px; flex-shrink: 0; }
    .wh-alert-value { font-size: 24px; font-weight: 800; color: var(--text); }
    .wh-alert-label { font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; }

    /* Stock cards — visual representation of each item */
    .wh-stock-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 12px;
    }
    .wh-stock-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        position: relative;
        transition: all .15s;
    }
    .wh-stock-card:hover { border-color: var(--accent); }
    .wh-stock-card.urgent { border-left: 4px solid #dc2626; }
    .wh-stock-card.low { border-left: 4px solid #f97316; }
    .wh-stock-card.ok { border-left: 4px solid #16a34a; }

    .wh-stock-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .wh-stock-name {
        font-size: 14px;
        font-weight: 700;
        color: var(--text);
    }
    .wh-stock-branch {
        font-size: 11px;
        color: var(--text-3);
        margin-top: 2px;
    }
    .wh-stock-status {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .wh-stock-status.critical { background: #fee2e2; color: #dc2626; }
    .wh-stock-status.warning { background: #fef3c7; color: #d97706; }
    .wh-stock-status.ok { background: #dcfce7; color: #16a34a; }

    /* Stock level bar — visual fill */
    .wh-stock-bar {
        height: 8px;
        background: #f3f4f6;
        border-radius: 4px;
        overflow: hidden;
        margin: 10px 0;
    }
    .wh-stock-fill {
        height: 100%;
        border-radius: 4px;
        transition: width .4s;
    }
    .wh-stock-fill.critical { background: #dc2626; }
    .wh-stock-fill.warning { background: #f97316; }
    .wh-stock-fill.ok { background: #16a34a; }

    .wh-stock-meta {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: var(--text-2);
    }
    .wh-stock-qty {
        font-size: 18px;
        font-weight: 800;
        color: var(--text);
    }
    .wh-stock-unit {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-3);
    }

    /* Quick action buttons on cards */
    .wh-stock-actions {
        display: flex;
        gap: 6px;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid var(--border);
    }
    .wh-action-btn {
        flex: 1;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        transition: all .15s;
    }
    .wh-action-btn:hover { border-color: var(--accent); color: var(--accent); }
    .wh-action-btn.primary { background: var(--accent); color: #fff; border-color: var(--accent); }
    .wh-action-btn.primary:hover { opacity: .9; }

    /* Sidebar panels */
    .wh-side-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
    }
    .wh-side-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 12px;
    }

    /* Expiry countdown */
    .wh-expiry-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--border);
    }
    .wh-expiry-item:last-child { border-bottom: none; }
    .wh-expiry-days {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        flex-shrink: 0;
    }
    .wh-expiry-days.critical { background: #fee2e2; color: #dc2626; }
    .wh-expiry-days.warning { background: #fef3c7; color: #d97706; }
    .wh-expiry-days.expired { background: #1f2937; color: #fff; }
    .wh-expiry-name { font-size: 12px; font-weight: 600; color: var(--text); flex: 1; }
    .wh-expiry-qty { font-size: 11px; color: var(--text-3); }

    /* Tabs */
    .wh-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 20px;
        background: #f3f4f6;
        padding: 4px;
        border-radius: 10px;
    }
    .wh-tab {
        flex: 1;
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        background: transparent;
        color: var(--text-2);
        cursor: pointer;
        transition: all .15s;
        text-align: center;
    }
    .wh-tab:hover { color: var(--text); }
    .wh-tab.active { background: #fff; color: var(--text); box-shadow: 0 1px 3px rgba(0,0,0,.08); }

    .wh-section { display: none; }
    .wh-section.active { display: block; }

    /* Filter bar */
    .wh-filter-bar {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        align-items: center;
    }
    .wh-filter-select {
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--border);
        background: #fff;
        color: var(--text);
        font-family: var(--font);
    }

    .empty-state {
        text-align: center;
        color: var(--text-3);
        font-size: 13px;
        padding: 40px 20px;
    }

    /* Data table for list views */
    .wh-table {
        width: 100%;
        border-collapse: collapse;
    }
    .wh-table th {
        text-align: left;
        padding: 10px 12px;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-3);
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 2px solid var(--border);
    }
    .wh-table td {
        padding: 12px;
        font-size: 13px;
        border-bottom: 1px solid var(--border);
    }
    .wh-table tr:hover td { background: #fafafa; }
@endsection

@section('content')
{{-- ═══ FILTER BAR ═══ --}}
<div class="wh-filter-bar">
    @if(!$branches->isEmpty() && $branches->count() > 1)
        <select class="wh-filter-select" onchange="setBranch(this.value)">
            <option value="">All Branches</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    @endif
    <a href="{{ route('purchase-orders.index') }}" class="wh-action-btn primary" style="margin-left:auto;text-decoration:none">
        + Create Purchase Order
    </a>
</div>

{{-- ═══ ALERT STRIP ═══ --}}
<div class="wh-alert-strip">
    <div class="wh-alert-card critical" onclick="switchTab('expiring')">
        <span class="wh-alert-icon">⚠️</span>
        <div>
            <div class="wh-alert-value">{{ $totalExpiring + $totalExpired }}</div>
            <div class="wh-alert-label">Expiry Alerts</div>
        </div>
    </div>
    <div class="wh-alert-card warning" onclick="switchTab('low-stock')">
        <span class="wh-alert-icon">📉</span>
        <div>
            <div class="wh-alert-value">{{ $totalLowStock }}</div>
            <div class="wh-alert-label">Low Stock</div>
        </div>
    </div>
    <div class="wh-alert-card out" onclick="switchTab('out-of-stock')">
        <span class="wh-alert-icon">🚫</span>
        <div>
            <div class="wh-alert-value">{{ $totalOutOfStock }}</div>
            <div class="wh-alert-label">Out of Stock</div>
        </div>
    </div>
</div>

{{-- ═══ TABS ═══ --}}
<div class="wh-tabs">
    <button class="wh-tab active" onclick="switchTab('expiring')">⚠️ Expiring</button>
    <button class="wh-tab" onclick="switchTab('low-stock')">📉 Low Stock</button>
    <button class="wh-tab" onclick="switchTab('out-of-stock')">🚫 Out of Stock</button>
    <button class="wh-tab" onclick="switchTab('waste')">🗑️ Waste Log</button>
</div>

{{-- ═══ MAIN CONTENT ═══ --}}
<div class="wh-grid">
    <div>
        {{-- Expiring Items --}}
        <div class="wh-section active" id="section-expiring">
            @if($expiringItems->isEmpty())
                <div class="wh-side-panel">
                    <div class="empty-state" style="padding:20px">
                        <div style="font-size:24px;margin-bottom:8px">✅</div>
                        <div style="font-weight:700;color:var(--text);margin-bottom:4px">All Clear!</div>
                        <div>No items expiring soon. Your stock is fresh.</div>
                        <div style="margin-top:16px;padding:12px;background:var(--bg);border-radius:8px;text-align:left;font-size:12px;color:var(--text-2)">
                            <div style="font-weight:600;color:var(--text);margin-bottom:6px">💡 How it works:</div>
                            <div>• Items expiring within 7 days appear here</div>
                            <div>• You can create a PO to reorder or mark items as waste</div>
                            <div>• Expiry dates are set when stock is received</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="wh-stock-grid">
                    @foreach($expiringItems as $item)
                        <div class="wh-stock-card {{ $item['status'] === 'expired' ? 'urgent' : ($item['status'] === 'critical' ? 'urgent' : 'low') }}">
                            <div class="wh-stock-header">
                                <div>
                                    <div class="wh-stock-name">{{ $item['ingredient'] }}</div>
                                    <div class="wh-stock-branch">{{ $item['branch'] }}</div>
                                </div>
                                <span class="wh-stock-status {{ $item['status'] }}">
                                    @if($item['status'] === 'expired')
                                        Expired
                                    @else
                                        {{ $item['days_until'] }}d left
                                    @endif
                                </span>
                            </div>
                            <div class="wh-stock-qty">{{ $item['quantity'] }} <span class="wh-stock-unit">{{ $item['unit'] }}</span></div>
                            <div class="wh-stock-meta">
                                <span>Expires {{ \Carbon\Carbon::parse($item['expires_at'])->format('M d') }}</span>
                            </div>
                            <div class="wh-stock-actions">
                                <a href="{{ route('purchase-orders.index') }}" class="wh-action-btn primary">Reorder</a>
                                <button class="wh-action-btn" onclick="alert('Mark as waste')">Discard</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Low Stock --}}
        <div class="wh-section" id="section-low-stock">
            @if($lowStockItems->isEmpty())
                <div class="wh-side-panel">
                    <div class="empty-state" style="padding:20px">
                        <div style="font-size:24px;margin-bottom:8px">✅</div>
                        <div style="font-weight:700;color:var(--text);margin-bottom:4px">Stock Levels Healthy</div>
                        <div>All items are above their minimum thresholds.</div>
                        <div style="margin-top:16px;padding:12px;background:var(--bg);border-radius:8px;text-align:left;font-size:12px;color:var(--text-2)">
                            <div style="font-weight:600;color:var(--text);margin-bottom:6px">📊 How it works:</div>
                            <div>• Items below their min threshold appear here</div>
                            <div>• We calculate daily usage to predict when you'll run out</div>
                            <div>• Click "Create PO" to reorder before stock runs out</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="wh-stock-grid">
                    @foreach($lowStockItems as $item)
                        @php
                            $fillPct = $item['min_threshold'] > 0 ? min(100, ($item['current'] / $item['min_threshold']) * 100) : 0;
                        @endphp
                        <div class="wh-stock-card {{ $item['status'] === 'critical' ? 'urgent' : 'low' }}">
                            <div class="wh-stock-header">
                                <div>
                                    <div class="wh-stock-name">{{ $item['ingredient'] }}</div>
                                    <div class="wh-stock-branch">{{ $item['branch'] }}</div>
                                </div>
                                <span class="wh-stock-status {{ $item['status'] }}">
                                    {{ $item['status'] === 'critical' ? 'Urgent' : 'Low' }}
                                </span>
                            </div>
                            <div style="display:flex;align-items:baseline;gap:6px">
                                <div class="wh-stock-qty">{{ $item['current'] }}</div>
                                <div class="wh-stock-unit">/ {{ $item['min_threshold'] }} {{ $item['unit'] }}</div>
                            </div>
                            <div class="wh-stock-bar">
                                <div class="wh-stock-fill {{ $item['status'] }}" style="width:{{ $fillPct }}%"></div>
                            </div>
                            <div class="wh-stock-meta">
                                <span>{{ $item['daily_usage'] }} {{ $item['unit'] }}/day usage</span>
                                <span style="color:{{ $item['days_until_empty'] <= 3 ? '#dc2626' : '#f97706' }};font-weight:700">
                                    {{ $item['days_until_empty'] }} days left
                                </span>
                            </div>
                            <div class="wh-stock-actions">
                                <a href="{{ route('purchase-orders.index') }}" class="wh-action-btn primary">Create PO</a>
                                <button class="wh-action-btn">Adjust Stock</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Out of Stock --}}
        <div class="wh-section" id="section-out-of-stock">
            @if($outOfStockItems->isEmpty())
                <div class="wh-side-panel">
                    <div class="empty-state" style="padding:16px">
                        <div style="font-size:20px;margin-bottom:4px">✅</div>
                        <div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:2px">All stocked up</div>
                        <div style="font-size:11px;color:var(--text-3)">No items are currently out of stock.</div>
                    </div>
                </div>
            @else
                <div class="wh-stock-grid">
                    @foreach($outOfStockItems as $item)
                        <div class="wh-stock-card urgent">
                            <div class="wh-stock-header">
                                <div>
                                    <div class="wh-stock-name">{{ $item['ingredient'] }}</div>
                                    <div class="wh-stock-branch">{{ $item['branch'] }}</div>
                                </div>
                                <span class="wh-stock-status critical">Out of Stock</span>
                            </div>
                            <div class="wh-stock-qty" style="color:#dc2626">0 <span class="wh-stock-unit">{{ $item['unit'] }}</span></div>
                            <div class="wh-stock-actions">
                                <a href="{{ route('purchase-orders.index') }}" class="wh-action-btn primary">Emergency PO</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Waste Log --}}
        <div class="wh-section" id="section-waste">
            <div class="wh-side-panel">
                <div class="wh-side-title">🗑️ Waste Log (Last 30 Days)</div>
                @if($recentWaste->isEmpty())
                    <div class="empty-state" style="padding:16px">
                        <div style="font-size:20px;margin-bottom:4px">✅</div>
                        <div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:2px">No waste logged</div>
                        <div style="font-size:11px;color:var(--text-3)">Waste entries from discarded items will appear here.</div>
                    </div>
                @else
                    <table class="wh-table">
                        <thead>
                            <tr><th>Item</th><th>Branch</th><th>Qty</th><th>Reason</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recentWaste as $waste)
                                <tr>
                                    <td style="font-weight:600">{{ $waste->branchStock?->ingredient?->name ?? '—' }}</td>
                                    <td>{{ $waste->branchStock?->branch?->name ?? '—' }}</td>
                                    <td style="color:#dc2626;font-weight:600">{{ abs($waste->quantity_change) }}</td>
                                    <td>{{ $waste->notes ?? '—' }}</td>
                                    <td>{{ $waste->created_at->format('M d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ SIDEBAR ═══ --}}
    <div>
        {{-- Expiring Soon Countdown --}}
        <div class="wh-side-panel">
            <div class="wh-side-title">⏰ Expiry Countdown</div>
            @php
                $expiringSorted = $expiringItems->sortBy('days_until')->take(8);
            @endphp
            @if($expiringSorted->isEmpty())
                <div style="text-align:center;padding:16px">
                    <div style="font-size:18px;margin-bottom:4px">✅</div>
                    <div style="font-size:11px;font-weight:600;color:var(--text);margin-bottom:2px">All clear</div>
                    <div style="font-size:10px;color:var(--text-3)">No items expiring soon.</div>
                </div>
            @else
                @foreach($expiringSorted as $item)
                    <div class="wh-expiry-item">
                        <div class="wh-expiry-days {{ $item['status'] }}">
                            @if($item['status'] === 'expired')
                                !
                            @else
                                {{ $item['days_until'] }}
                            @endif
                        </div>
                        <div style="flex:1">
                            <div class="wh-expiry-name">{{ $item['ingredient'] }}</div>
                            <div class="wh-expiry-qty">{{ $item['quantity'] }} {{ $item['unit'] }} · {{ $item['branch'] }}</div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Quick Stats --}}
        <div class="wh-side-panel">
            <div class="wh-side-title">📊 Stock Health</div>
            <div style="display:flex;flex-direction:column;gap:12px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:var(--text-2)">Healthy Items</span>
                    <span style="font-size:14px;font-weight:700;color:#16a34a">✓</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:var(--text-2)">Expiring Soon</span>
                    <span style="font-size:14px;font-weight:700;color:#f97316">{{ $totalExpiring }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:var(--text-2)">Already Expired</span>
                    <span style="font-size:14px;font-weight:700;color:#dc2626">{{ $totalExpired }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:var(--text-2)">Low Stock</span>
                    <span style="font-size:14px;font-weight:700;color:#f97316">{{ $totalLowStock }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:var(--text-2)">Out of Stock</span>
                    <span style="font-size:14px;font-weight:700;color:#dc2626">{{ $totalOutOfStock }}</span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="wh-side-panel">
            <div class="wh-side-title">⚡ Quick Actions</div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <a href="{{ route('purchase-orders.index') }}" class="wh-action-btn" style="text-decoration:none;text-align:left;padding:10px 12px">
                    📋 Create Purchase Order
                </a>
                <a href="{{ route('inventory.intelligence') }}" class="wh-action-btn" style="text-decoration:none;text-align:left;padding:10px 12px">
                    🔄 Refresh Stock Levels
                </a>
                <a href="{{ route('suppliers.index') }}" class="wh-action-btn" style="text-decoration:none;text-align:left;padding:10px 12px">
                    📦 View Suppliers
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tab) {
        document.querySelectorAll('.wh-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.wh-section').forEach(s => s.classList.remove('active'));

        // Find and activate the matching tab and section
        document.querySelectorAll('.wh-tab').forEach(t => {
            if (t.textContent.toLowerCase().includes(tab.replace('-', ' ')) || 
                (tab === 'expiring' && t.textContent.includes('Expiring')) ||
                (tab === 'low-stock' && t.textContent.includes('Low Stock')) ||
                (tab === 'out-of-stock' && t.textContent.includes('Out of Stock')) ||
                (tab === 'waste' && t.textContent.includes('Waste'))) {
                t.classList.add('active');
            }
        });
        document.getElementById('section-' + tab).classList.add('active');
    }

    function setBranch(branchId) {
        const url = new URL(window.location.href);
        if (branchId) {
            url.searchParams.set('branch_id', branchId);
        } else {
            url.searchParams.delete('branch_id');
        }
        window.location.href = url.toString();
    }
</script>
@endsection
