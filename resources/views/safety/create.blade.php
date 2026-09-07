@extends('layouts.sidebar')

@section('title', 'New Safety Checklist')

@section('styles')
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .form-label { font-size: 12px; font-weight: 600; color: var(--accent); }
    .form-input { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: var(--font); }

    .checklist-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        margin-bottom: 10px;
    }
    .checklist-item input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin-top: 2px;
        flex-shrink: 0;
    }
    .checklist-item__text {
        flex: 1;
    }
    .checklist-item__label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
    }
    .checklist-item__notes {
        margin-top: 8px;
    }
    .checklist-item__notes input {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 12px;
        font-family: var(--font);
    }

    .btn-submit { padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; background: #16a34a; color: #fff; cursor: pointer; }

    .back-link { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--text-2); text-decoration: none; margin-bottom: 16px; }
    .back-link:hover { color: var(--accent); }
@endsection

@section('content')
<a href="{{ route('safety.index') }}" class="back-link">← Back to Checklists</a>

<div class="inv-panel">
    <div class="inv-panel__title">New Health & Safety Checklist</div>

    <form action="{{ route('safety.store') }}" method="POST">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-input" required>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="inv-panel__title" style="margin-top:20px">Checklist Items</div>

        @foreach($defaultItems as $i => $item)
        <div class="checklist-item">
            <input type="checkbox" name="items[{{ $i }}][passed]" value="1" id="item-{{ $i }}">
            <input type="hidden" name="items[{{ $i }}][passed]" value="0">
            <div class="checklist-item__text">
                <label class="checklist-item__label" for="item-{{ $i }}">{{ $item['item'] }}</label>
                <div class="checklist-item__notes">
                    <input type="text" name="items[{{ $i }}][notes]" placeholder="Notes (optional)">
                </div>
                <input type="hidden" name="items[{{ $i }}][item]" value="{{ $item['item'] }}">
            </div>
        </div>
        @endforeach

        <div class="form-group" style="margin-top:20px">
            <label class="form-label">Overall Notes</label>
            <textarea name="overall_notes" class="form-input" rows="3" placeholder="Any additional observations..."></textarea>
        </div>

        <button type="submit" class="btn-submit">Submit Checklist</button>
    </form>
</div>
@endsection
