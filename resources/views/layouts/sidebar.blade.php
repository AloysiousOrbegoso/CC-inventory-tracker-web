<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — InvenTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Tailwind's preflight already zeroes margin/padding and sets
           border-box on every element. Repeating it here is not just
           redundant — this block is unlayered, so a `*` reset would outrank
           every @layer components rule and silently flatten card padding. */

        body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

        /* ═══ SIDEBAR ═══ */
        .sidebar {
            width: var(--sidebar-w); height: 100vh; position: fixed; left: 0; top: 0;
            background: var(--card); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; z-index: 100;
            padding: 20px 0;
            overflow: hidden;
        }

        .sidebar__brand {
            display: flex; align-items: center; gap: 10px;
            padding: 0 20px; margin-bottom: 28px;
        }
        .sidebar__brand-icon {
            width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }
        .sidebar__brand-text { font-size: 18px; font-weight: 900; color: var(--text); letter-spacing: -.02em; }

        .sidebar__nav { flex: 1; display: flex; flex-direction: column; gap: 2px; padding: 0 12px; overflow-y: auto; }

        /* Nav reads as a terracotta list, matching the approved design. */
        .sidebar__link {
            display: flex; align-items: center; gap: 12px;
            padding: 9px 14px; border-radius: 10px;
            font-size: 13.5px; font-weight: 600; color: var(--text-2);
            border-left: 3px solid transparent;
            text-decoration: none; transition: all .15s;
        }
        .sidebar__link:hover { background: var(--color-accent-light); }
        .sidebar__link.is-active {
            background: var(--color-accent-light);
            color: var(--text);
            border-left-color: var(--accent);
            font-weight: 800;
        }
        .sidebar__link svg { width: 17px; height: 17px; flex-shrink: 0; }
        .sidebar__link .badge-count {
            margin-left: auto; background: var(--accent-2); color: #fff;
            font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px;
        }

        /* Collapsible group headers */
        .sidebar__group {
            margin-bottom: 4px;
        }
        .sidebar__group-header {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 14px; border-radius: 8px;
            font-size: 11px; font-weight: 700; color: var(--text-3);
            text-transform: uppercase; letter-spacing: .06em;
            cursor: pointer; user-select: none;
            transition: all .15s;
        }
        .sidebar__group-header:hover { color: var(--text-2); }
        .sidebar__group-header svg {
            width: 12px; height: 12px; flex-shrink: 0;
            transition: transform .2s;
        }
        .sidebar__group.open .sidebar__group-header svg {
            transform: rotate(90deg);
        }
        .sidebar__group-items {
            display: none;
            padding-left: 8px;
            animation: slideDown .2s ease;
        }
        .sidebar__group.open .sidebar__group-items {
            display: block;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Groups are separated by a rule rather than a caption, so the nav
           stays a single uninterrupted column of destinations. */
        .sidebar__divider {
            height: 1px; background: var(--border);
            margin: 12px 14px;
        }

        .sidebar__footer {
            padding: 14px 14px 0; margin-top: auto;
        }
        .sidebar__user {
            display: flex; align-items: center; gap: 10px; padding: 0 0 12px;
        }
        .sidebar__user-name { font-size: 12px; font-weight: 700; color: var(--text); }
        .sidebar__user-role { font-size: 10px; color: var(--text-3); font-weight: 600; }

        /* Tinted pill, matching the weight of every other nav row — Log Out
           reads as "leave" rather than a loud destructive button. */
        .sidebar__logout {
            display: flex; align-items: center; gap: 10px;
            width: 100%; padding: 9px 14px; border-radius: 10px;
            font-size: 13.5px; font-weight: 700; color: var(--accent-2);
            background: rgba(214, 48, 49, 0.08); border: none; cursor: pointer;
            font-family: var(--font); transition: all .15s;
        }
        .sidebar__logout:hover { background: rgba(214, 48, 49, 0.14); }
        .sidebar__logout svg { width: 17px; height: 17px; flex-shrink: 0; }

        /* ═══ MAIN ═══ */
        .main { margin-left: var(--sidebar-w); flex: 1; min-width: 0; overflow-x: hidden; display: flex; flex-direction: column; min-height: 100vh; }
        /* Capped so cards stop stretching edge-to-edge on wide monitors —
           past ~1280px the extra width just widens gaps, not content. */
        .content { flex: 1; padding: 16px; max-width: 1280px; width: 100%; margin: 0 auto; }

        .content-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .content-title { font-size: 22px; font-weight: 800; letter-spacing: -.02em; }
        .content-subtitle { font-size: 13px; color: var(--text-2); margin-top: 2px; }
        .content-date { font-size: 13px; color: var(--text-2); font-weight: 500; }

        /* Top navigation pills removed per user request */

        /* Shared components (.card, .badge, .btn-primary, .data-table, …)
           now live in resources/css/app.css. */
        .card__link { font-size: 12px; font-weight: 600; color: var(--accent); text-decoration: none; }
        .card__link:hover { text-decoration: underline; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(225,112,85,.3); }

        /* ═══ RESPONSIVE ═══ */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .content { padding: 16px; }
            .topbar { padding: 0 16px; }
            .topbar__user-name, .topbar__user-email { display: none; }
        }

        /* ═══ PAGE-SPECIFIC ═══ */
        @yield('styles')
    </style>
</head>
<body>

@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
    $roleName = $user->isSuperAdmin() ? 'Owner' : ($user->isManager() ? 'Manager' : 'Staff');
    $pendingAlertCount = \App\Models\DiscrepancyAlert::where('status', 'pending')->count();
    $pendingApplicantCount = \App\Models\JobApplicant::where('status', 'applied')->count();
    $currentRoute = request()->route()?->getName() ?? '';

    // Mirrors the route middleware. The gate is enforced server-side; hiding
    // the links here just avoids offering a user a door that 403s.
    $isOwner = $user->isSuperAdmin();
    $canSeeFinancials = $user->hasRole(\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_MANAGER);

    // Employees link: point to the first branch's workers tab
    $employeeBranch = $user->isManager()
        ? \App\Models\Branch::find($user->branch_id)
        : \App\Models\Branch::where('status', 'active')->first();
    $employeesUrl = $employeeBranch ? route('branches.show', $employeeBranch) . '?tab=workers' : route('branches');

    // Active-state helpers — each link gets its own check so only one highlights
    $isBusinessActive = $currentRoute === 'branches';
    $currentTab = request()->query('tab', '');
    $isEmployeesActive = $currentRoute === 'branches.show' && $currentTab === 'workers';
@endphp

<aside class="sidebar">
    <div class="sidebar__brand">
        <img src="{{ asset('images/logo.svg') }}" alt="InvenTrack" style="height: 32px; width: auto;">
    </div>

    <nav class="sidebar__nav">
        @unless ($user->isStaff())
        <a href="{{ route('dashboard') }}" class="sidebar__link {{ $currentRoute === 'dashboard' ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="{{ route('map') }}" class="sidebar__link {{ $currentRoute === 'map' ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Map
        </a>
        @endunless

        @if ($canSeeFinancials)
        {{-- ── Planning ──────────────────────────────────────── --}}
        @php
            $isPlanningOpen = str_starts_with($currentRoute, 'calendar') || str_starts_with($currentRoute, 'leave');
        @endphp
        <div class="sidebar__group {{ $isPlanningOpen ? 'open' : '' }}">
            <div class="sidebar__group-header" onclick="toggleGroup(this.parentElement, 'planning')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                Planning
            </div>
            <div class="sidebar__group-items">
                <a href="{{ route('calendar') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'calendar') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Calendar
                </a>
                <a href="{{ route('leave.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'leave') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/></svg>
                    Leave Requests
                </a>
            </div>
        </div>

        {{-- ── Business ──────────────────────────────────────── --}}
        @php
            $isBusinessOpen = $isBusinessActive || $isEmployeesActive || str_starts_with($currentRoute, 'hiring') || str_starts_with($currentRoute, 'customers');
        @endphp
        <div class="sidebar__group {{ $isBusinessOpen ? 'open' : '' }}">
            <div class="sidebar__group-header" onclick="toggleGroup(this.parentElement, 'business')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                Business
            </div>
            <div class="sidebar__group-items">
                <a href="{{ route('branches') }}" class="sidebar__link {{ $isBusinessActive ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    Branches
                </a>
                <a href="{{ $employeesUrl }}" class="sidebar__link {{ $isEmployeesActive ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    Employees
                </a>
                <a href="{{ route('hiring.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'hiring') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Hiring
                    @if ($pendingApplicantCount > 0)
                        <span class="badge-count">{{ $pendingApplicantCount }}</span>
                    @endif
                </a>
                <a href="{{ route('customers.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'customers') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Customers
                </a>
            </div>
        </div>

        {{-- ── Finance ───────────────────────────────────────── --}}
        @php
            $isFinanceOpen = str_starts_with($currentRoute, 'payments') || str_starts_with($currentRoute, 'receipts') || str_starts_with($currentRoute, 'salary') || $currentRoute === 'profit-loss' || str_starts_with($currentRoute, 'invoices');
        @endphp
        <div class="sidebar__group {{ $isFinanceOpen ? 'open' : '' }}">
            <div class="sidebar__group-header" onclick="toggleGroup(this.parentElement, 'finance')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                Finance
            </div>
            <div class="sidebar__group-items">
                <a href="{{ route('profit-loss') }}" class="sidebar__link {{ $currentRoute === 'profit-loss' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Profit & Loss
                </a>
                <a href="{{ route('payments.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'payments') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Payments
                </a>
                <a href="{{ route('invoices.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'invoices') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
                    Invoices
                </a>
                <a href="{{ route('salary.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'salary') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                    Salary
                </a>
                <a href="{{ route('receipts.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'receipts') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
                    Receipts
                </a>
            </div>
        </div>

        {{-- ── Analytics ─────────────────────────────────────── --}}
        @php
            $isAnalyticsOpen = str_starts_with($currentRoute, 'analytics') || str_starts_with($currentRoute, 'reports') || $currentRoute === 'forecasting' || $currentRoute === 'benchmarking';
        @endphp
        <div class="sidebar__group {{ $isAnalyticsOpen ? 'open' : '' }}">
            <div class="sidebar__group-header" onclick="toggleGroup(this.parentElement, 'analytics')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                Analytics
            </div>
            <div class="sidebar__group-items">
                <a href="{{ route('analytics') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'analytics') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('reports') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'reports') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Reports
                </a>
                <a href="{{ route('reports.custom') }}" class="sidebar__link {{ $currentRoute === 'reports.custom' || $currentRoute === 'reports.custom.generate' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
                    Custom Reports
                </a>
                <a href="{{ route('forecasting') }}" class="sidebar__link {{ $currentRoute === 'forecasting' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Forecasting
                </a>
                <a href="{{ route('benchmarking') }}" class="sidebar__link {{ $currentRoute === 'benchmarking' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    Benchmarking
                </a>
            </div>
        </div>

        {{-- ── Operations ────────────────────────────────────── --}}
        @php
            $isOpsOpen = str_starts_with($currentRoute, 'inventory') || str_starts_with($currentRoute, 'purchase-orders') || $currentRoute === 'pricing.ingredients';
        @endphp
        <div class="sidebar__group {{ $isOpsOpen ? 'open' : '' }}">
            <div class="sidebar__group-header" onclick="toggleGroup(this.parentElement, 'operations')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                Operations
            </div>
            <div class="sidebar__group-items">
                <a href="{{ route('inventory.intelligence') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'inventory') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    Inventory
                </a>
                <a href="{{ route('purchase-orders.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'purchase-orders') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
                    Purchase Orders
                </a>
                <a href="{{ route('pricing.ingredients') }}" class="sidebar__link {{ $currentRoute === 'pricing.ingredients' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Ingredient Pricing
                </a>
            </div>
        </div>

        {{-- ── Compliance ────────────────────────────────────── --}}
        @php
            $isComplianceOpen = str_starts_with($currentRoute, 'equipment') || str_starts_with($currentRoute, 'safety') || str_starts_with($currentRoute, 'audit') || str_starts_with($currentRoute, 'legal-papers');
        @endphp
        <div class="sidebar__group {{ $isComplianceOpen ? 'open' : '' }}">
            <div class="sidebar__group-header" onclick="toggleGroup(this.parentElement, 'compliance')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                Compliance
            </div>
            <div class="sidebar__group-items">
                <a href="{{ route('equipment.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'equipment') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Equipment
                </a>
                <a href="{{ route('safety.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'safety') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Health & Safety
                </a>
                <a href="{{ route('audit.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'audit') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Audit Trail
                </a>
                <a href="{{ route('legal-papers.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'legal-papers') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="M12 22V12"/><path d="M3 7l9 5 9-5"/></svg>
                    Legal Papers
                </a>
            </div>
        </div>
        @endif

        <div class="sidebar__divider"></div>

        {{-- ── Settings & Help ───────────────────────────────── --}}
        <a href="{{ route('settings') }}" class="sidebar__link {{ $currentRoute === 'settings' ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Settings
        </a>
        <a href="{{ route('notices.index') }}" class="sidebar__link {{ str_starts_with($currentRoute, 'notices') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Mail/Messages
        </a>
        <a href="{{ route('help') }}" class="sidebar__link {{ $currentRoute === 'help' ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Help Center
        </a>
    </nav>

    {{-- The signed-in user now lives in the top bar; the footer keeps sign-out
         plus the role, which is the one piece of identity worth showing twice. --}}
    <div class="sidebar__footer">
        <div class="sidebar__user">
            <div>
                <div class="sidebar__user-role">Signed in as</div>
                <div class="sidebar__user-name">{{ $roleName }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar__logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Log Out
            </button>
        </form>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <div class="topbar__title">@yield('title', 'Dashboard')</div>



        <div class="topbar__actions">
            <a href="{{ route('help') }}" class="topbar__icon" title="Help">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </a>
            <div class="relative">
                <button type="button" class="topbar__icon" id="notifBellBtn" title="Notifications" onclick="toggleNotifDropdown()">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span id="notifDot" class="dot" style="display:none"></span>
                </button>
                <div id="notifDropdown" class="card p-0" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:340px;max-height:400px;overflow-y:auto;z-index:200;">
                    <div class="flex items-center justify-between px-3 py-2 border-b border-line">
                        <span class="text-[12px] font-bold">Notifications</span>
                        <button type="button" id="notifMarkAllBtn" onclick="markAllNotifsRead()" class="text-[11px] font-semibold text-accent bg-transparent border-none cursor-pointer p-0 hover:underline" style="display:none;">Mark all read</button>
                    </div>
                    <div id="notifList" class="p-3 text-[12px] text-ink-3">Loading…</div>
                    <a href="{{ route('alerts') }}" class="flex items-center justify-center py-2.5 text-[12px] font-semibold text-accent no-underline hover:underline border-t border-line">View all alerts</a>
                </div>
            </div>

            <span class="topbar__divider"></span>

            <a href="{{ route('settings') }}" class="topbar__user">
                <span class="avatar">{{ $initials }}</span>
                <span>
                    <span class="topbar__user-name">{{ $user->name }}</span><br>
                    <span class="topbar__user-email">{{ $user->email }}</span>
                </span>
            </a>
        </div>
    </header>

    <div class="content">
        {{-- Pages that declare a subtitle let the layout render their header;
             the rest supply their own heading markup inside @section('content'). --}}
        @hasSection('subtitle')
            <div class="content-header">
                <div>
                    <h1 class="content-title">@yield('title', 'Dashboard')</h1>
                    <div class="content-subtitle">@yield('subtitle')</div>
                </div>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const dropdown = document.getElementById('notifDropdown');
        const list = document.getElementById('notifList');
        const dot = document.getElementById('notifDot');
        const markAllBtn = document.getElementById('notifMarkAllBtn');

        function escapeHtml(s) {
            const div = document.createElement('div');
            div.textContent = s ?? '';
            return div.innerHTML;
        }

        function renderNotifications(data) {
            dot.style.display = data.unread_count > 0 ? '' : 'none';
            markAllBtn.style.display = data.unread_count > 0 ? '' : 'none';

            if (data.notifications.length === 0) {
                list.innerHTML = '<div style="text-align:center;padding:20px 12px;color:var(--color-ink-3)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="display:block;margin:0 auto 8px;opacity:.35"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span style="font-size:12px;font-weight:600">No notifications yet.</span></div>';
                return;
            }

            list.innerHTML = data.notifications.map(n => `
                <div class="px-3 py-2.5 border-b border-line ${n.read_at ? 'opacity-55' : ''} cursor-pointer hover:bg-accent-light"
                     onclick="markNotifRead(${n.id}, this)">
                    <div class="text-[12.5px] font-bold">${escapeHtml(n.title)}</div>
                    <div class="text-[11.5px] text-ink-2 mt-0.5">${escapeHtml(n.message)}</div>
                </div>
            `).join('');
        }

        // One fetch on page load covers both the dot state and the dropdown's
        // contents — no need for a second round-trip when the bell is clicked.
        fetch('{{ route('notifications.index') }}', { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(renderNotifications)
            .catch(() => { list.innerHTML = '<div class="p-3 text-[12px] text-red-600">Failed to load notifications.</div>'; });

        window.toggleNotifDropdown = function () {
            dropdown.style.display = dropdown.style.display !== 'none' ? 'none' : '';
        };

        window.markNotifRead = function (id, el) {
            fetch(`{{ url('/notifications') }}/${id}/read`, {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            }).then(res => {
                if (res.ok) {
                    el.classList.add('opacity-55');
                    const remaining = list.querySelectorAll('div:not(.opacity-55)').length;
                    dot.style.display = remaining > 0 ? '' : 'none';
                    markAllBtn.style.display = remaining > 0 ? '' : 'none';
                }
            });
        };

        window.markAllNotifsRead = function () {
            markAllBtn.disabled = true;
            fetch('{{ route('notifications.read-all') }}', {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            }).then(res => {
                markAllBtn.disabled = false;
                if (res.ok) {
                    list.querySelectorAll('#notifList > div').forEach(el => el.classList.add('opacity-55'));
                    dot.style.display = 'none';
                    markAllBtn.style.display = 'none';
                }
            });
        };

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target) && e.target.id !== 'notifBellBtn' && !document.getElementById('notifBellBtn').contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    })();

    // ═══ SIDEBAR STATE PERSISTENCE ═══
    // Runs immediately to prevent visual jumping
    (function() {
        const STORAGE_KEY = 'sidebar_state';
        
        function getState() {
            try {
                return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || {};
            } catch(e) {
                return {};
            }
        }
        
        function saveState(state) {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        }
        
        // Toggle group and save
        window.toggleGroup = function(groupEl, groupName) {
            groupEl.classList.toggle('open');
            const state = getState();
            state.groups = state.groups || {};
            state.groups[groupName] = groupEl.classList.contains('open');
            saveState(state);
        };
        
        // Run IMMEDIATELY — not on DOMContentLoaded
        const state = getState();
        const nav = document.querySelector('.sidebar__nav');
        
        // Restore group states immediately
        if (state.groups) {
            document.querySelectorAll('.sidebar__group').forEach(group => {
                const header = group.querySelector('.sidebar__group-header');
                if (header) {
                    const onclick = header.getAttribute('onclick') || '';
                    const match = onclick.match(/toggleGroup\([^,]+,\s*'([^']+)'/);
                    if (match) {
                        const groupName = match[1];
                        if (state.groups.hasOwnProperty(groupName)) {
                            if (state.groups[groupName]) {
                                group.classList.add('open');
                            } else {
                                group.classList.remove('open');
                            }
                        }
                    }
                }
            });
        }
        
        // Restore scroll position immediately
        if (nav && state.scrollY) {
            nav.scrollTop = state.scrollY;
        }
        
        // Save scroll position on scroll (debounced)
        if (nav) {
            let scrollTimeout;
            nav.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function() {
                    const currentState = getState();
                    currentState.scrollY = nav.scrollTop;
                    saveState(currentState);
                }, 50);
            });
        }
    })();
</script>

</body>
</html>
