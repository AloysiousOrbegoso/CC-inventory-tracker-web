<div class="dash-main">

    <div class="two-up">
        <div class="widget">
            <div class="widget-head">
                <h2>Current Business Activities</h2>
            </div>
            @if ($recent_transactions->isEmpty())
                <div class="empty-state-icon" style="padding:24px 16px">
                    <div style="font-size:24px;margin-bottom:6px">🧾</div>
                    <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px">No transactions yet</div>
                    <div style="font-size:11px;color:var(--text-3)">Sales will appear here once staff process orders.</div>
                </div>
            @else
                <table class="alerts-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Amount</th>
                            <th>Staff</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recent_transactions as $txn)
                            <tr>
                                <td>{{ $txn->created_at->format('M d, g:iA') }}</td>
                                <td>{{ $txn->product->name ?? '—' }}</td>
                                <td>{{ $txn->quantity }}</td>
                                <td class="cell-primary">&#8369;{{ number_format($txn->total_amount, 2) }}</td>
                                <td>{{ $txn->user->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="widget">
            <div class="widget-head">
                <h2>Performance &mdash; Last 7 Days</h2>
            </div>
            @php
                $chartDays = collect();
                for ($i = 6; $i >= 0; $i--) {
                    $date = \Carbon\Carbon::today()->subDays($i);
                    $match = $daily_sales->firstWhere('date', $date->format('Y-m-d'));
                    $chartDays->push([
                        'label' => substr($date->format('D'), 0, 1),
                        'total' => $match ? (float) $match->total : 0,
                    ]);
                }
                $chartMax = $chartDays->max('total');
            @endphp
            @if ($chartMax > 0)
                <div class="bar-chart">
                    @foreach ($chartDays as $day)
                        <div class="bar-col">
                            <div class="bar" style="height: {{ max(2, ($day['total'] / $chartMax) * 100) }}%"></div>
                            <div class="bar-day-label">{{ $day['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state-icon" style="padding:20px 16px">
                    <div style="font-size:22px;margin-bottom:6px">📊</div>
                    <div style="font-size:12px;font-weight:600;color:var(--text)">No sales this week</div>
                    <div style="font-size:11px;color:var(--text-3)">Daily sales chart will populate as orders come in.</div>
                </div>
            @endif
        </div>
    </div>

    <div class="two-up">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">&#8369;{{ number_format($total_revenue, 0) }}</div>
            <span class="stat-note">Profit margin unavailable &mdash; no cost data.</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">Leakage Incidents</div>
            <div class="stat-value">{{ $leakage_rows->count() }}</div>
            <span class="stat-note">Negative shift-count variances on record.</span>
        </div>
    </div>

    <div class="widget">
        <div class="widget-head">
            <h2>Leakage</h2>
        </div>
        @if ($leakage_rows->isEmpty())
            <div class="all-clear">No leakage detected &#10003;</div>
        @else
            <table class="alerts-table">
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Expected</th>
                        <th>Actual</th>
                        <th>Variance</th>
                        <th>Staff</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($leakage_rows as $row)
                        <tr>
                            <td>{{ $row->ingredient->name ?? '—' }}</td>
                            <td>{{ rtrim(rtrim(number_format($row->closing_quantity_expected, 3), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format($row->closing_quantity_actual, 3), '0'), '.') }}</td>
                            <td class="variance-cell">{{ number_format($row->variance, 2) }}</td>
                            <td>{{ $row->shiftLog?->user?->name ?? '—' }}</td>
                            <td>{{ $row->created_at->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="widget">
        <div class="widget-head">
            <h2>Historical Trend &mdash; {{ now()->year }}</h2>
        </div>
        @php $monthMax = $monthly_sales->max(); @endphp
        @if ($monthly_sales->count() >= 2 && $monthMax > 0)
            <div class="bar-chart">
                @foreach ($monthly_sales as $month => $total)
                    <div class="bar-col">
                        <div class="bar" style="height: {{ max(2, ($total / $monthMax) * 100) }}%"></div>
                        <div class="bar-day-label">{{ \Carbon\Carbon::parse($month . '-01')->format('M') }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state" style="padding:20px">
                <div style="font-size:20px;margin-bottom:6px">📈</div>
                <div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:2px">Not enough data for trends</div>
                <div style="font-size:11px;color:var(--text-3)">Monthly revenue trend will show after a few months of data.</div>
            </div>
        @endif
    </div>

</div>
