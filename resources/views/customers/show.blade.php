@extends('layouts.sidebar')

@section('title', $customer->name)

@section('styles')
    .cust-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    @media (max-width: 800px) { .cust-detail-grid { grid-template-columns: 1fr; } }

    .cust-panel {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 24px;
    }
    .cust-panel__title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 16px;
    }
    .cust-info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }
    .cust-info-row:last-child { border-bottom: none; }
    .cust-info-row__label { color: var(--text-2); font-weight: 500; }
    .cust-info-row__value { font-weight: 600; color: var(--text); }

    .tier-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }
    .tier-badge.bronze { background: #fed7aa; color: #9a3412; }
    .tier-badge.silver { background: #e5e7eb; color: #374151; }
    .tier-badge.gold { background: #fef08a; color: #854d0e; }
    .tier-badge.platinum { background: #ddd6fe; color: #5b21b6; }

    .loyalty-bar {
        height: 12px;
        background: var(--bg);
        border-radius: 6px;
        overflow: hidden;
        margin: 12px 0;
    }
    .loyalty-fill {
        height: 100%;
        border-radius: 6px;
        background: var(--accent);
        transition: width .4s;
    }

    .star { color: #fbbf24; font-size: 16px; }
    .star-empty { color: #d1d5db; font-size: 16px; }

    .feedback-item {
        padding: 16px 0;
        border-bottom: 1px solid var(--border);
    }
    .feedback-item:last-child { border-bottom: none; }
    .feedback-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .feedback-category {
        font-size: 11px;
        font-weight: 600;
        color: var(--accent);
        text-transform: uppercase;
    }
    .feedback-date {
        font-size: 11px;
        color: var(--text-3);
    }
    .feedback-comment {
        font-size: 13px;
        color: var(--text-2);
        line-height: 1.5;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-2);
        text-decoration: none;
        margin-bottom: 16px;
    }
    .back-link:hover { color: var(--accent); }

    .flash-success {
        background: #dcfce7;
        border: 1px solid #16a34a;
        color: #166534;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 13px;
    }
@endsection

@section('content')
@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

<a href="{{ route('customers.index') }}" class="back-link">← Back to Customers</a>

<div class="cust-detail-grid">
    <!-- Customer Info -->
    <div class="cust-panel">
        <div class="cust-panel__title">Customer Profile</div>
        <div style="text-align:center;margin-bottom:20px">
            <div style="width:60px;height:60px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;margin:0 auto 12px">
                {{ strtoupper(substr($customer->name, 0, 2)) }}
            </div>
            <div style="font-size:18px;font-weight:700;color:var(--text)">{{ $customer->name }}</div>
            <div class="tier-badge {{ $customer->tier }}" style="margin-top:8px">{{ ucfirst($customer->tier) }} Member</div>
        </div>
        <div class="cust-info-row">
            <span class="cust-info-row__label">Email</span>
            <span class="cust-info-row__value">{{ $customer->email ?? '—' }}</span>
        </div>
        <div class="cust-info-row">
            <span class="cust-info-row__label">Phone</span>
            <span class="cust-info-row__value">{{ $customer->phone ?? '—' }}</span>
        </div>
        <div class="cust-info-row">
            <span class="cust-info-row__label">Branch</span>
            <span class="cust-info-row__value">{{ $customer->branch?->name ?? '—' }}</span>
        </div>
        <div class="cust-info-row">
            <span class="cust-info-row__label">Member Since</span>
            <span class="cust-info-row__value">{{ $customer->created_at->format('M d, Y') }}</span>
        </div>
    </div>

    <!-- Loyalty Info -->
    <div class="cust-panel">
        <div class="cust-panel__title">Loyalty Program</div>
        <div style="text-align:center;margin-bottom:20px">
            <div style="font-size:32px;font-weight:800;color:var(--accent)">{{ $customer->loyalty_points }}</div>
            <div style="font-size:12px;color:var(--text-3);text-transform:uppercase;letter-spacing:.04em">Loyalty Points</div>
        </div>

        @php
            $tierThresholds = ['bronze' => 0, 'silver' => 200, 'gold' => 500, 'platinum' => 1000];
            $nextTier = match($customer->tier) {
                'bronze' => 'silver',
                'silver' => 'gold',
                'gold' => 'platinum',
                default => null,
            };
            $currentMin = $tierThresholds[$customer->tier];
            $nextMin = $nextTier ? $tierThresholds[$nextTier] : 1000;
            $progress = $nextTier ? (($customer->loyalty_points - $currentMin) / ($nextMin - $currentMin)) * 100 : 100;
        @endphp

        @if($nextTier)
        <div style="font-size:12px;color:var(--text-2);margin-bottom:4px">
            {{ $nextMin - $customer->loyalty_points }} points to {{ ucfirst($nextTier) }}
        </div>
        <div class="loyalty-bar">
            <div class="loyalty-fill" style="width:{{ min(100, $progress) }}%"></div>
        </div>
        @else
        <div style="font-size:12px;color:var(--text-3);text-align:center">最高 Max tier reached!</div>
        @endif

        <div class="cust-info-row">
            <span class="cust-info-row__label">Total Spent</span>
            <span class="cust-info-row__value">₱{{ number_format($customer->total_spent, 2) }}</span>
        </div>
        <div class="cust-info-row">
            <span class="cust-info-row__label">Total Visits</span>
            <span class="cust-info-row__value">{{ $customer->total_visits }}</span>
        </div>
        <div class="cust-info-row">
            <span class="cust-info-row__label">Last Visit</span>
            <span class="cust-info-row__value">{{ $customer->last_visit_at ? $customer->last_visit_at->format('M d, Y') : 'Never' }}</span>
        </div>

        <!-- Add Points Form -->
        <form action="{{ route('customers.add-points', $customer) }}" method="POST" style="margin-top:16px;display:flex;gap:8px">
            @csrf
            <input type="number" name="points" class="form-input" placeholder="Points" min="1" required style="flex:1">
            <button type="submit" class="btn-submit" style="width:auto;margin:0">Add Points</button>
        </form>
    </div>
</div>

<!-- Feedback Section -->
<div class="cust-panel">
    <div class="cust-panel__title">Customer Feedback ({{ $feedbackStats['total_reviews'] }} reviews)</div>

    @if($feedbackStats['total_reviews'] > 0)
    <div style="display:flex;gap:24px;margin-bottom:20px;padding:16px;background:var(--bg);border-radius:8px">
        <div style="text-align:center">
            <div style="font-size:24px;font-weight:800;color:var(--accent)">{{ number_format($feedbackStats['avg_rating'], 1) }}</div>
            <div style="font-size:11px;color:var(--text-3)">Avg Rating</div>
        </div>
        <div style="flex:1">
            @foreach([5, 4, 3, 2, 1] as $star)
                @php
                    $count = $customer->feedback->where('rating', $star)->count();
                    $pct = $feedbackStats['total_reviews'] > 0 ? ($count / $feedbackStats['total_reviews']) * 100 : 0;
                @endphp
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <span style="font-size:11px;width:20px;text-align:right">{{ $star }}★</span>
                    <div style="flex:1;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden">
                        <div style="height:100%;width:{{ $pct }}%;background:#fbbf24;border-radius:4px"></div>
                    </div>
                    <span style="font-size:10px;color:var(--text-3);width:30px">{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Add Feedback Form -->
    <form action="{{ route('customers.feedback.store', $customer) }}" method="POST" style="margin-bottom:20px;padding:16px;background:var(--bg);border-radius:8px">
        @csrf
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
            <div class="form-group" style="flex:1;min-width:150px">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-input" required>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3">3 - Average</option>
                    <option value="2">2 - Below Average</option>
                    <option value="1">1 - Poor</option>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:150px">
                <label class="form-label">Category</label>
                <select name="category" class="form-input">
                    <option value="">Select category</option>
                    <option value="service">Service</option>
                    <option value="food">Food</option>
                    <option value="ambiance">Ambiance</option>
                    <option value="value">Value</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group" style="flex:2;min-width:200px">
                <label class="form-label">Comment</label>
                <input type="text" name="comment" class="form-input" placeholder="Optional feedback comment">
            </div>
            <button type="submit" class="btn-submit" style="width:auto;margin:0;padding:10px 20px">Submit</button>
        </div>
    </form>

    <!-- Feedback List -->
    @if($customer->feedback->isEmpty())
        <div class="empty-state" style="padding:20px">
            <div style="font-size:22px;margin-bottom:6px">💬</div>
            <div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:2px">No feedback yet</div>
            <div style="font-size:11px;color:var(--text-3)">Customer reviews and ratings will appear here.</div>
        </div>
    @else
        @foreach($customer->feedback->latest()->get() as $fb)
            <div class="feedback-item">
                <div class="feedback-header">
                    <div>
                        @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $fb->rating ? 'star' : 'star-empty' }}">★</span>
                        @endfor
                        @if($fb->category)
                            <span class="feedback-category" style="margin-left:8px">{{ $fb->category }}</span>
                        @endif
                    </div>
                    <span class="feedback-date">{{ $fb->created_at->format('M d, Y') }}</span>
                </div>
                @if($fb->comment)
                    <div class="feedback-comment">{{ $fb->comment }}</div>
                @endif
            </div>
        @endforeach
    @endif
</div>
@endsection
