<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\SafetyChecklist;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SafetyChecklistController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();
        $branchId = $request->query('branch_id');

        $query = SafetyChecklist::with(['branch', 'completer']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($isManager) {
            $query->where('branch_id', $user->branch_id);
        }

        $checklists = $query->latest()->paginate(20);

        // Stats
        $statsQuery = SafetyChecklist::query();
        if ($isManager) {
            $statsQuery->where('branch_id', $user->branch_id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'passed' => (clone $statsQuery)->where('status', 'pass')->count(),
            'failed' => (clone $statsQuery)->where('status', 'fail')->count(),
            'last_check' => (clone $statsQuery)->latest()->first()?->check_date,
        ];

        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('safety.index', [
            'checklists' => $checklists,
            'stats' => $stats,
            'branchId' => $branchId,
            'branches' => $branches,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();

        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('safety.create', [
            'branches' => $branches,
            'defaultItems' => SafetyChecklist::defaultItems(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers run safety checks.']);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array',
            'items.*.item' => 'required|string',
            'items.*.passed' => 'required|boolean',
            'items.*.notes' => 'nullable|string',
            'overall_notes' => 'nullable|string',
        ]);

        if ($user->isManager() && $user->branch_id !== (int) $validated['branch_id']) {
            return back()->withErrors(['branch_id' => 'Forbidden.']);
        }

        $allPassed = collect($validated['items'])->every(fn($item) => $item['passed'] === true || $item['passed'] === '1');
        $nonePassed = collect($validated['items'])->every(fn($item) => $item['passed'] === false || $item['passed'] === '0');

        $status = $allPassed ? 'pass' : ($nonePassed ? 'fail' : 'partial');

        SafetyChecklist::create([
            'branch_id' => $validated['branch_id'],
            'completed_by' => $user->id,
            'check_date' => now(),
            'status' => $status,
            'items' => $validated['items'],
            'overall_notes' => $validated['overall_notes'] ?? null,
        ]);

        return redirect()->route('safety.index')
            ->with('success', 'Safety checklist submitted with status: ' . ucfirst($status) . '.');
    }

    public function show(SafetyChecklist $checklist): View
    {
        $checklist->load(['branch', 'completer']);

        return view('safety.show', [
            'checklist' => $checklist,
        ]);
    }
}
