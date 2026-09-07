@extends('layouts.sidebar')

@section('title', 'Customers')

@section('styles')
    /* ═══ CRM STYLE ═══ */
    .crm-grid {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 20px;
    }
    @media (max-width: 1024px) { .crm-grid { grid-template-columns: 1fr; } }

    /* Loyalty tier cards */
    .crm-tiers {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
    }
    .crm-tier-card {
        flex: 1;
        padding: 16px;
        border-radius: 12px;
        border: 1.5px solid var(--border);
        background: #fff;
        cursor: pointer;
        transition: all .15s;
    }
    .crm-tier-card:hover { border-color: var(--accent); }
    .crm-tier-card.active { border-color: var(--accent); background: var(--color-accent-light); }
    .crm-tier-card__icon { font-size: 20px; margin-bottom: 8px; }
    .crm-tier-card__value { font-size: 24px; font-weight: 800; color: var(--text); }
    .crm-tier-card__label { font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }

    /* Customer cards — people-centric */
    .crm-customer-card {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all .15s;
    }
    .crm-customer-card:hover { border-color: var(--accent); }
    .crm-customer-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .crm-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .crm-avatar.bronze { background: #fed7aa; color: #9a3412; }
    .crm-avatar.silver { background: #e5e7eb; color: #374151; }
    .crm-avatar.gold { background: #fef08a; color: #854d0e; }
    .crm-avatar.platinum { background: #ddd6fe; color: #5b21b6; }

    .crm-customer-name { font-size: 15px; font-weight: 700; color: var(--text); }
    .crm-customer-contact { font-size: 12px; color: var(--text-2); margin-top: 2px; }
    .crm-customer-tier {
        margin-left: auto;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .crm-customer-tier.bronze { background: #fed7aa; color: #9a3412; }
    .crm-customer-tier.silver { background: #e5e7eb; color: #374151; }
    .crm-customer-tier.gold { background: #fef08a; color: #854d0e; }
    .crm-customer-tier.platinum { background: #ddd6fe; color: #5b21b6; }

    .crm-customer-stats {
        display: flex;
        gap: 16px;
        padding: 12px 0;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        margin-bottom: 12px;
    }
    .crm-customer-stat {
        flex: 1;
        text-align: center;
    }
    .crm-customer-stat__value { font-size: 16px; font-weight: 800; color: var(--text); }
    .crm-customer-stat__label { font-size: 10px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; }

    .crm-loyalty-bar {
        height: 6px;
        background: #f3f4f6;
        border-radius: 3px;
        overflow: hidden;
        margin: 8px 0;
    }
    .crm-loyalty-fill {
        height: 100%;
        border-radius: 3px;
        transition: width .4s;
    }
    .crm-loyalty-fill.bronze { background: #d97706; }
    .crm-loyalty-fill.silver { background: #6b7280; }
    .crm-loyalty-fill.gold { background: #eab308; }
    .crm-loyalty-fill.platinum { background: #8b5cf6; }

    .crm-customer-actions {
        display: flex;
        gap: 6px;
    }
    .crm-action-btn {
        flex: 1;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        transition: all .15s;
    }
    .crm-action-btn:hover { border-color: var(--accent); color: var(--accent); }
    .crm-action-btn.primary { background: var(--accent); color: #fff; border-color: var(--accent); }
    .crm-action-btn.primary:hover { opacity: .9; }

    /* Sidebar */
    .crm-side-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
    }
    .crm-side-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 12px;
    }

    /* Search */
    .crm-search {
        padding: 10px 14px;
        border-radius: 10px;
        border: 1.5px solid var(--border);
        font-size: 13px;
        font-family: var(--font);
        width: 100%;
        margin-bottom: 16px;
    }
    .crm-search:focus { outline: none; border-color: var(--accent); }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-content {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        width: 90%;
        max-width: 500px;
    }
    .modal-title { font-size: 20px; font-weight: 700; color: var(--text); margin-bottom: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .form-label { font-size: 12px; font-weight: 600; color: var(--accent); }
    .form-input { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: var(--font); }
    .btn-submit { margin-top: 12px; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; background: #16a34a; color: #fff; cursor: pointer; width: 100%; }

    .flash-success { background: #dcfce7; border: 1px solid #16a34a; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    .empty-state { text-align: center; color: var(--text-3); font-size: 13px; padding: 40px 20px; }
@endsection

@section('content')
@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

{{-- ═══ SEARCH ═══ --}}
<input type="text" class="crm-search" placeholder="🔍 Search customers by name, email, or phone..." value="{{ $search ?? '' }}"
       onkeypress="if(event.key==='Enter') setSearch(this.value)">

{{-- ═══ LOYALTY TIERS ═══ --}}
<div class="crm-tiers">
    <div class="crm-tier-card {{ ($tier ?? '') === '' ? 'active' : '' }}" onclick="setTier('')">
        <div class="crm-tier-card__icon">👥</div>
        <div class="crm-tier-card__value">{{ $stats['total'] }}</div>
        <div class="crm-tier-card__label">All Customers</div>
    </div>
    <div class="crm-tier-card {{ ($tier ?? '') === 'bronze' ? 'active' : '' }}" onclick="setTier('bronze')">
        <div class="crm-tier-card__icon">🥉</div>
        <div class="crm-tier-card__value">{{ $stats['bronze'] }}</div>
        <div class="crm-tier-card__label">Bronze</div>
    </div>
    <div class="crm-tier-card {{ ($tier ?? '') === 'silver' ? 'active' : '' }}" onclick="setTier('silver')">
        <div class="crm-tier-card__icon">🥈</div>
        <div class="crm-tier-card__value">{{ $stats['silver'] }}</div>
        <div class="crm-tier-card__label">Silver</div>
    </div>
    <div class="crm-tier-card {{ ($tier ?? '') === 'gold' ? 'active' : '' }}" onclick="setTier('gold')">
        <div class="crm-tier-card__icon">🥇</div>
        <div class="crm-tier-card__value">{{ $stats['gold'] }}</div>
        <div class="crm-tier-card__label">Gold</div>
    </div>
    <div class="crm-tier-card {{ ($tier ?? '') === 'platinum' ? 'active' : '' }}" onclick="setTier('platinum')">
        <div class="crm-tier-card__icon">💎</div>
        <div class="crm-tier-card__value">{{ $stats['platinum'] }}</div>
        <div class="crm-tier-card__label">Platinum</div>
    </div>
</div>

{{-- ═══ MAIN CONTENT ═══ --}}
<div class="crm-grid">
    <div>
        @if($customers->isEmpty())
            <div class="crm-side-panel">
                <div class="empty-state" style="padding:24px">
                    <div style="font-size:40px;margin-bottom:12px">👥</div>
                    <div style="font-size:16px;font-weight:700;color:var(--text);margin-bottom:6px">No Customers Yet</div>
                    <div style="font-size:13px;color:var(--text-2);margin-bottom:16px">Add your first customer to start building relationships and tracking loyalty.</div>
                    <div style="padding:14px;background:var(--bg);border-radius:10px;text-align:left;font-size:12px;color:var(--text-2);margin-bottom:12px">
                        <div style="font-weight:600;color:var(--text);margin-bottom:8px">🏆 Loyalty Tiers:</div>
                        <div style="margin-bottom:4px">🥉 <strong>Bronze</strong> — 0+ points (default)</div>
                        <div style="margin-bottom:4px">🥈 <strong>Silver</strong> — 200+ points</div>
                        <div style="margin-bottom:4px">🥇 <strong>Gold</strong> — 500+ points</div>
                        <div>💎 <strong>Platinum</strong> — 1000+ points</div>
                    </div>
                    <div style="padding:14px;background:var(--bg);border-radius:10px;text-align:left;font-size:12px;color:var(--text-2);margin-bottom:16px">
                        <div style="font-weight:600;color:var(--text);margin-bottom:8px">💰 Earning Points:</div>
                        <div style="margin-bottom:4px">• 10 points for leaving feedback</div>
                        <div style="margin-bottom:4px">• Points for purchases (configurable)</div>
                        <div>• Bonus points for referrals</div>
                    </div>
                    <button class="crm-action-btn primary" style="flex:none;padding:10px 20px;font-size:13px" onclick="openCreateModal()">+ Add First Customer</button>
                </div>
            </div>
        @else
            @foreach($customers as $cust)
                @php
                    $tierThresholds = ['bronze' => 0, 'silver' => 200, 'gold' => 500, 'platinum' => 1000];
                    $nextTier = match($cust->tier) { 'bronze' => 'silver', 'silver' => 'gold', 'gold' => 'platinum', default => null };
                    $currentMin = $tierThresholds[$cust->tier];
                    $nextMin = $nextTier ? $tierThresholds[$nextTier] : 1000;
                    $progress = $nextTier ? (($cust->loyalty_points - $currentMin) / ($nextMin - $currentMin)) * 100 : 100;
                @endphp
                <div class="crm-customer-card">
                    <div class="crm-customer-header">
                        <div class="crm-avatar {{ $cust->tier }}">
                            {{ strtoupper(substr($cust->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="crm-customer-name">{{ $cust->name }}</div>
                            <div class="crm-customer-contact">{{ $cust->email ?? '—' }} {{ $cust->phone ? '· ' . $cust->phone : '' }}</div>
                        </div>
                        <span class="crm-customer-tier {{ $cust->tier }}">{{ ucfirst($cust->tier) }}</span>
                    </div>

                    <div class="crm-customer-stats">
                        <div class="crm-customer-stat">
                            <div class="crm-customer-stat__value">{{ $cust->loyalty_points }}</div>
                            <div class="crm-customer-stat__label">Points</div>
                        </div>
                        <div class="crm-customer-stat">
                            <div class="crm-customer-stat__value">₱{{ number_format($cust->total_spent, 0) }}</div>
                            <div class="crm-customer-stat__label">Total Spent</div>
                        </div>
                        <div class="crm-customer-stat">
                            <div class="crm-customer-stat__value">{{ $cust->total_visits }}</div>
                            <div class="crm-customer-stat__label">Visits</div>
                        </div>
                    </div>

                    @if($nextTier)
                    <div style="font-size:11px;color:var(--text-2);margin-bottom:4px">
                        {{ $nextMin - $cust->loyalty_points }} points to {{ ucfirst($nextTier) }}
                    </div>
                    <div class="crm-loyalty-bar">
                        <div class="crm-loyalty-fill {{ $cust->tier }}" style="width:{{ min(100, $progress) }}%"></div>
                    </div>
                    @endif

                    <div class="crm-customer-actions">
                        <a href="{{ route('customers.show', $cust) }}" class="crm-action-btn">View Profile</a>
                        @if(auth()->user()->isManager())
                        <button class="crm-action-btn primary" onclick="addPoints({{ $cust->id }}, '{{ $cust->name }}')">+ Add Points</button>
                        @endif
                    </div>
                </div>
            @endforeach
            <div style="margin-top:16px">{{ $customers->links() }}</div>
        @endif
    </div>

    {{-- ═══ SIDEBAR ═══ --}}
    <div>
        {{-- Quick Stats --}}
        <div class="crm-side-panel">
            <div class="crm-side-title">📊 Customer Stats</div>
            <div style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:12px;color:var(--text-2)">Total Customers</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text)">{{ $stats['total'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:12px;color:var(--text-2)">Gold + Platinum</span>
                    <span style="font-size:12px;font-weight:700;color:#eab308">{{ $stats['gold'] + $stats['platinum'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:12px;color:var(--text-2)">Total Revenue</span>
                    <span style="font-size:12px;font-weight:700;color:#16a34a">₱{{ number_format($stats['totalSpent'], 0) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:12px;color:var(--text-2)">Avg. Spend</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text)">
                        ₱{{ $stats['total'] > 0 ? number_format($stats['totalSpent'] / $stats['total'], 0) : '0' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Top Customers --}}
        <div class="crm-side-panel">
            <div class="crm-side-title">🏆 Top Customers</div>
            @php
                $topCustomers = \App\Models\Customer::orderByDesc('total_spent')->take(5)->get();
            @endphp
            @if($topCustomers->isEmpty())
                <div style="text-align:center;color:var(--text-3);padding:16px;font-size:12px">No data yet.</div>
            @else
                @foreach($topCustomers as $i => $c)
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
                        <span style="width:20px;font-size:11px;font-weight:700;color:var(--text-3);text-align:center">{{ $i + 1 }}</span>
                        <div class="crm-avatar {{ $c->tier }}" style="width:28px;height:28px;font-size:10px">
                            {{ strtoupper(substr($c->name, 0, 2)) }}
                        </div>
                        <div style="flex:1">
                            <div style="font-size:12px;font-weight:600;color:var(--text)">{{ $c->name }}</div>
                            <div style="font-size:11px;color:var(--text-3)">{{ $c->loyalty_points }} pts</div>
                        </div>
                        <div style="font-size:12px;font-weight:700;color:var(--accent)">₱{{ number_format($c->total_spent, 0) }}</div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Quick Actions --}}
        <div class="crm-side-panel">
            <div class="crm-side-title">⚡ Quick Actions</div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <button class="crm-action-btn" style="text-align:left" onclick="openCreateModal()">+ Add New Customer</button>
                <a href="{{ route('customers.index') }}" class="crm-action-btn" style="text-align:left;text-decoration:none">📋 View All Customers</a>
            </div>
        </div>
    </div>
</div>

{{-- ═══ ADD CUSTOMER MODAL ═══ --}}
<div class="modal-overlay" id="createModal">
    <div class="modal-content">
        <div class="modal-title">Add Customer</div>
        <form action="{{ route('customers.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-input" required>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-input" placeholder="Customer name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" placeholder="email@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-input" placeholder="+63...">
            </div>
            <button type="submit" class="btn-submit">Add Customer</button>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.add('active');
    }
    document.getElementById('createModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });

    function setTier(tier) {
        const url = new URL(window.location.href);
        if (tier) {
            url.searchParams.set('tier', tier);
        } else {
            url.searchParams.delete('tier');
        }
        window.location.href = url.toString();
    }

    function setSearch(search) {
        const url = new URL(window.location.href);
        if (search) {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }
        window.location.href = url.toString();
    }

    function addPoints(customerId, name) {
        const points = prompt(`Add loyalty points to ${name}:`);
        if (points && !isNaN(points) && parseInt(points) > 0) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/customers/${customerId}/points`;
            form.innerHTML = `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="points" value="${points}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endsection
