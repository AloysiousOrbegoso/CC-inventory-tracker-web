@php
    use App\Models\StockMovement;
    if (!isset($stockItems) || $stockItems->isEmpty()) {
        $stockItems = collect([
            (object)['item_name'=>'Milk','unit'=>'L','branch_name'=>'QC Main','estimated_amount'=>100,'on_site_amount'=>100,'min_threshold'=>20,'status'=>'ok'],
            (object)['item_name'=>'Sugar','unit'=>'kg','branch_name'=>'QC Main','estimated_amount'=>100,'on_site_amount'=>95,'min_threshold'=>15,'status'=>'ok'],
            (object)['item_name'=>'Coffee','unit'=>'kg','branch_name'=>'QC Main','estimated_amount'=>100,'on_site_amount'=>100,'min_threshold'=>10,'status'=>'ok'],
            (object)['item_name'=>'Cream','unit'=>'L','branch_name'=>'QC Main','estimated_amount'=>100,'on_site_amount'=>85,'min_threshold'=>20,'status'=>'low'],
            (object)['item_name'=>'Cups','unit'=>'pcs','branch_name'=>'QC Main','estimated_amount'=>100,'on_site_amount'=>100,'min_threshold'=>30,'status'=>'ok'],
        ]);
    }
    if (!isset($recentMovements) || $recentMovements->isEmpty()) { $recentMovements = collect(); }
    if (!isset($activeAlerts) || $activeAlerts->isEmpty()) { $activeAlerts = collect(); }
    if (!isset($recentTransactions) || $recentTransactions->isEmpty()) { $recentTransactions = collect(); }
@endphp

@extends('layouts.sidebar')

@section('title', 'Logistics')

@section('content')
<div class="flex items-center justify-between mb-6 max-[900px]:flex-col max-[900px]:items-start max-[900px]:gap-3">
    <div class="flex items-baseline gap-2.5">
        <h1 class="text-[22px] font-extrabold tracking-tight">Logistics</h1>
        <span class="text-sm text-ink-3">/ {{ auth()->user()->isSuperAdmin() ? 'Owner' : 'Manager' }}</span>
    </div>
    <div class="flex gap-1.5">
        <a href="{{ url('/logistics') }}?tab=summary" class="{{ $tab === 'summary' ? 'bg-accent text-white border-accent' : 'bg-card text-ink-2 border-line hover:border-accent hover:text-accent' }} px-[18px] py-[7px] rounded-full text-[13px] font-semibold border-[1.5px] no-underline transition-all duration-150">Summary</a>
        <a href="{{ url('/logistics') }}?tab=flags" class="{{ $tab === 'flags' ? 'bg-accent text-white border-accent' : 'bg-card text-ink-2 border-line hover:border-accent hover:text-accent' }} px-[18px] py-[7px] rounded-full text-[13px] font-semibold border-[1.5px] no-underline transition-all duration-150">Flags</a>
    </div>
</div>

@if ($tab === 'summary')
<div class="summary-table-wrap mb-6">
    <div class="summary-table-wrap__head">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        <span class="summary-table-wrap__title">Stock Reconciliation by Item</span>
    </div>
    <table class="summary-table">
        <thead><tr><th>Item</th><th>Branch</th><th>Estimated</th><th>On-site</th><th>Leakage</th><th>Inventory</th><th>Remarks</th></tr></thead>
        <tbody>
            @forelse ($stockItems as $item)
                @php
                    $onSite = (float) $item->on_site_amount;
                    $est = (float) $item->estimated_amount;
                    $pct = $est > 0 ? ($onSite / $est) * 100 : 100;
                    if ($pct >= 95) { $leakLabel = 'None'; $leakClass = 'sev-none'; }
                    elseif ($pct >= 85) { $leakLabel = 'Mild'; $leakClass = 'sev-mild'; }
                    elseif ($pct >= 70) { $leakLabel = 'Moderate'; $leakClass = 'sev-moderate'; }
                    else { $leakLabel = 'Severe'; $leakClass = 'sev-severe'; }
                    if ($item->status === 'out') { $invLabel = 'Out'; $invClass = 'badge-red'; }
                    elseif ($item->status === 'low') { $invLabel = 'Low'; $invClass = 'badge-amber'; }
                    else { $invLabel = 'Stocked'; $invClass = 'badge-green'; }
                    $remarkColor = $pct >= 95 ? 'var(--green)' : ($pct >= 85 ? '#e17055' : '#d63031');
                    $remark = $pct >= 95 ? 'Normal' : ($pct >= 85 ? 'Low' : ($pct >= 70 ? 'Moderate' : 'Critical'));
                @endphp
                <tr>
                    <td class="font-semibold">{{ $item->item_name }} <span class="opacity-40 text-[11px]">{{ $item->unit }}</span></td>
                    <td>{{ $item->branch_name }}</td>
                    <td class="num">{{ number_format($est, $item->unit === 'pcs' ? 0 : 1) }} {{ $item->unit }}</td>
                    <td class="num">{{ number_format($onSite, $item->unit === 'pcs' ? 0 : 1) }} {{ $item->unit }}</td>
                    <td><span class="severity-badge {{ $leakClass }}">{{ $leakLabel }}</span></td>
                    <td><span class="badge {{ $invClass }}">{{ $invLabel }}</span></td>
                    <td style="font-weight:700;color:{{ $remarkColor }}">{{ $remark }}</td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <div class="empty-state-icon" style="padding:32px 20px">
                        <div style="font-size:32px;margin-bottom:8px">📦</div>
                        <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">No stock items yet</div>
                        <div style="font-size:12px;color:var(--text-2)">Stock reconciliation data will appear here once you have inventory and transactions.</div>
                    </div>
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
    <div class="card px-[18px] py-4">
        <div class="text-xs font-bold uppercase tracking-[.05em] text-ink-3">Leakage Indicator</div>
        <div class="mt-2">
            <div class="text-xs flex justify-between py-1"><span>Normal</span><span class="font-bold text-green">&ge; 95%</span></div>
            <div class="text-xs flex justify-between py-1"><span>Mild</span><span class="font-bold text-[#a16207]">85% – 94%</span></div>
            <div class="text-xs flex justify-between py-1"><span>Moderate</span><span class="font-bold text-[#c2410c]">70% – 84%</span></div>
            <div class="text-xs flex justify-between py-1"><span>Severe</span><span class="font-bold text-[#d63031]">&lt; 70%</span></div>
        </div>
    </div>
    <div class="card px-[18px] py-4">
        <div class="text-xs font-bold uppercase tracking-[.05em] text-ink-3">Inventory Indicator</div>
        <div class="mt-2">
            <div class="text-xs flex justify-between py-1"><span>Stocked</span><span class="badge badge-green">OK</span></div>
            <div class="text-xs flex justify-between py-1"><span>Low</span><span class="badge badge-amber">Low</span></div>
            <div class="text-xs flex justify-between py-1"><span>Out</span><span class="badge badge-red">Out</span></div>
        </div>
    </div>
    <div class="card px-[18px] py-4">
        <div class="text-xs font-bold uppercase tracking-[.05em] text-ink-3">Summary</div>
        @php
            $totalItems = $stockItems->count();
            $flaggedItems = $stockItems->filter(fn($i) => (float)$i->on_site_amount < (float)$i->estimated_amount)->count();
            $leakItems = $stockItems->filter(fn($i) => (float)$i->on_site_amount < (float)$i->estimated_amount * 0.85)->count();
        @endphp
        <div class="mt-2">
            <div class="text-xs flex justify-between py-1"><span>Total Items</span><span class="font-bold">{{ $totalItems }}</span></div>
            <div class="text-xs flex justify-between py-1"><span>With Variance</span><span class="font-bold text-[#e17055]">{{ $flaggedItems }}</span></div>
            <div class="text-xs flex justify-between py-1"><span>Probable Leak</span><span class="font-bold text-[#d63031]">{{ $leakItems }}</span></div>
        </div>
    </div>
</div>
@endif

@if ($tab === 'flags')
<div class="summary-table-wrap mb-6">
    <div class="summary-table-wrap__head">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
        <span class="summary-table-wrap__title">Active Discrepancy Flags</span>
    </div>
    <table class="summary-table">
        <thead><tr><th>Item</th><th>Branch</th><th>Expected</th><th>Actual</th><th>Leakage</th><th>Status</th><th>Remarks</th></tr></thead>
        <tbody>
            @forelse ($activeAlerts as $alert)
                @php
                    $variance = $alert->variance;
                    $absPct = $alert->expected_value > 0 ? abs($variance / $alert->expected_value) * 100 : 0;
                    $sev = $alert->severity ?? 'none';
                @endphp
                <tr>
                    <td class="font-semibold">{{ $alert->ingredient?->name ?? 'Raw Materials' }}</td>
                    <td>{{ $alert->branch?->name ?? '—' }}</td>
                    <td>{{ number_format($alert->expected_value, 2) }} {{ $alert->ingredient?->unit ?? '' }}</td>
                    <td>{{ number_format($alert->actual_value, 2) }} {{ $alert->ingredient?->unit ?? '' }}</td>
                    <td><span class="severity-badge sev-{{ $sev }}">{{ ucfirst($sev) }}</span></td>
                    <td><span class="badge badge-red">Flagged</span></td>
                    <td style="font-weight:600;color:{{ $absPct > 15 ? '#d63031' : ($absPct > 5 ? '#e17055' : 'var(--green)') }}">
                        @if ($absPct > 15) Low @elseif ($absPct > 5) Moderate @else Normal @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-8 text-center text-[13px] text-ink-3">No active flags — all stock levels are normal.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="grid grid-cols-2 gap-4 max-[900px]:grid-cols-1">
    <div class="card">
        <div class="card__head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-60"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1"/><circle cx="3" cy="12" r="1"/><circle cx="3" cy="18" r="1"/></svg><span class="card__title">Variables</span></div>
        <div class="card__body">
            <ul class="list-none">
                <li class="flex items-baseline gap-2 py-2.5 border-b border-line text-[13px]"><span class="font-semibold text-ink-2 min-w-[160px]">Constant Float Value</span><span class="font-bold">&#8369;200</span></li>
                <li class="flex items-baseline gap-2 py-2.5 border-b border-line text-[13px]"><span class="font-semibold text-ink-2 min-w-[160px]">Expected Total Sales</span><span class="font-bold">Total EOD Transactions</span></li>
                <li class="flex items-baseline gap-2 py-2.5 text-[13px]"><span class="font-semibold text-ink-2 min-w-[160px]">Total Inventory</span><span class="font-bold">{{ $totalStockItems }} items</span></li>
            </ul>
        </div>
        <div class="px-5 py-3 border-t border-line flex justify-end"><button class="btn-sm">Edit</button></div>
    </div>
    <div class="card">
        <div class="card__head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-60"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span class="card__title">Remarks</span></div>
        <div class="card__body">
            <p class="text-xs text-ink-2 mb-3.5 leading-relaxed">Indicator rules for Leakage &amp; Inventory status thresholds.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-[.06em] text-ink-3 mb-2.5 pb-2 border-b border-line">Leakage Indicator</h4>
                    <div class="flex justify-between items-center py-[7px] text-xs border-b border-line"><span>Normal</span><span class="w-[7px] h-[7px] rounded-full bg-[#00b894]"></span></div>
                    <div class="flex justify-between items-center py-[7px] text-xs border-b border-line"><span>Running Low</span><span class="w-[7px] h-[7px] rounded-full bg-[#fdcb6e]"></span></div>
                    <div class="flex justify-between items-center py-[7px] text-xs border-b border-line"><span>Low</span><span class="w-[7px] h-[7px] rounded-full bg-[#e17055]"></span></div>
                    <div class="flex justify-between items-center py-[7px] text-xs border-b border-line"><span>Almost Out</span><span class="w-[7px] h-[7px] rounded-full bg-[#d63031]"></span></div>
                    <div class="flex justify-between items-center py-[7px] text-xs"><span>Out</span><span class="w-[7px] h-[7px] rounded-full bg-[#d63031]"></span></div>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-[.06em] text-ink-3 mb-2.5 pb-2 border-b border-line">Inventory Indicator</h4>
                    <div class="flex justify-between items-center py-[7px] text-xs border-b border-line"><span>Normal</span><span class="w-[7px] h-[7px] rounded-full bg-[#00b894]"></span></div>
                    <div class="flex justify-between items-center py-[7px] text-xs border-b border-line"><span>Running Low</span><span class="w-[7px] h-[7px] rounded-full bg-[#fdcb6e]"></span></div>
                    <div class="flex justify-between items-center py-[7px] text-xs border-b border-line"><span>Low</span><span class="w-[7px] h-[7px] rounded-full bg-[#e17055]"></span></div>
                    <div class="flex justify-between items-center py-[7px] text-xs border-b border-line"><span>Almost Out</span><span class="w-[7px] h-[7px] rounded-full bg-[#d63031]"></span></div>
                    <div class="flex justify-between items-center py-[7px] text-xs"><span>Out</span><span class="w-[7px] h-[7px] rounded-full bg-[#d63031]"></span></div>
                </div>
            </div>
        </div>
        <div class="px-5 py-3 border-t border-line flex justify-end"><button class="btn-sm">Edit</button></div>
    </div>
    <div class="card">
        <div class="card__head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-60"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r=".5" fill="currentColor"/></svg><span class="card__title">Float Amount Discrepancy</span></div>
        <div class="card__body"><p class="text-xs text-ink-2 mb-3.5">Verifies that the amount left in the till is exact.</p><div class="bg-[rgba(214,48,49,.04)] border border-[rgba(214,48,49,.15)] rounded-[10px] p-3.5 px-4"><div class="text-[13px] font-bold text-[#d63031]">Actual Till Amount / Constant Float Value</div><div class="text-[11px] text-ink-2 mt-1.5">Must equate to 1 to be true — a flag is sent otherwise.</div></div></div>
        <div class="px-5 py-3 border-t border-line flex justify-end"><button class="btn-sm">Edit</button></div>
    </div>
    <div class="card">
        <div class="card__head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-60"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg><span class="card__title">Total Sales Discrepancy</span></div>
        <div class="card__body"><p class="text-xs text-ink-2 mb-3.5">Tracks variance between cash collected and expected revenue.</p><div class="bg-[rgba(214,48,49,.04)] border border-[rgba(214,48,49,.15)] rounded-[10px] p-3.5 px-4"><div class="text-[13px] font-bold text-[#d63031]">Actual Cash / Expected Total Sales</div></div></div>
        <div class="px-5 py-3 border-t border-line flex justify-end"><button class="btn-sm">Edit</button></div>
    </div>
    <div class="card">
        <div class="card__head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-60"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg><span class="card__title">EOD Inventory Discrepancy</span></div>
        <div class="card__body"><p class="text-xs text-ink-2 mb-3.5">Compares end-of-day physical count against expected inventory levels.</p><div class="bg-[rgba(214,48,49,.04)] border border-[rgba(214,48,49,.15)] rounded-[10px] p-3.5 px-4"><div class="text-[13px] font-bold text-[#d63031]">Actual Inventory Left / Expected Inventory Left</div></div></div>
        <div class="px-5 py-3 border-t border-line flex justify-end"><button class="btn-sm">Edit</button></div>
    </div>
    <div class="card">
        <div class="card__head"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-60"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span class="card__title">Leakage &amp; Inventory Breakdown</span></div>
        <div class="card__body">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-[.06em] text-ink-3 mb-2.5 pb-2 border-b border-line">Leakage Indicator</h4>
                    <div class="flex justify-between py-[7px] text-xs border-b border-line"><span>Normal</span><span class="font-bold text-green">&lt; 5%</span></div>
                    <div class="flex justify-between py-[7px] text-xs border-b border-line"><span>Running Low</span><span class="font-bold text-[#e17055]">5% – 10%</span></div>
                    <div class="flex justify-between py-[7px] text-xs border-b border-line"><span>Low</span><span class="font-bold text-[#e17055]">10% – 15%</span></div>
                    <div class="flex justify-between py-[7px] text-xs border-b border-line"><span>Almost Out</span><span class="font-bold text-[#d63031]">15% – 20%</span></div>
                    <div class="flex justify-between py-[7px] text-xs"><span>Out</span><span class="font-bold text-[#d63031]">&gt; 20%</span></div>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-[.06em] text-ink-3 mb-2.5 pb-2 border-b border-line">Inventory Indicator</h4>
                    <div class="flex justify-between py-[7px] text-xs border-b border-line"><span>Normal</span><span class="font-bold text-green">&gt; 60%</span></div>
                    <div class="flex justify-between py-[7px] text-xs border-b border-line"><span>Running Low</span><span class="font-bold text-[#e17055]">40% – 60%</span></div>
                    <div class="flex justify-between py-[7px] text-xs border-b border-line"><span>Low</span><span class="font-bold text-[#e17055]">20% – 40%</span></div>
                    <div class="flex justify-between py-[7px] text-xs border-b border-line"><span>Almost Out</span><span class="font-bold text-[#d63031]">10% – 20%</span></div>
                    <div class="flex justify-between py-[7px] text-xs"><span>Out</span><span class="font-bold text-[#d63031]">&lt; 10%</span></div>
                </div>
            </div>
        </div>
        <div class="px-5 py-3 border-t border-line flex justify-end"><button class="btn-sm">Edit</button></div>
    </div>
</div>
@endif
@endsection
