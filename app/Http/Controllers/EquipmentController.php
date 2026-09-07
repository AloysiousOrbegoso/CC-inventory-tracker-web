<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();
        $branchId = $request->query('branch_id');
        $status = $request->query('status');

        $query = Equipment::with('branch');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($isManager) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $equipment = $query->orderBy('name')->paginate(20);

        // Stats
        $statsQuery = Equipment::query();
        if ($isManager) {
            $statsQuery->where('branch_id', $user->branch_id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', 'active')->count(),
            'maintenance_needed' => (clone $statsQuery)->where('status', 'maintenance_needed')->count(),
            'out_of_service' => (clone $statsQuery)->where('status', 'out_of_service')->count(),
            'maintenance_due' => (clone $statsQuery)->where('next_maintenance', '<=', Carbon::now())->count(),
        ];

        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('equipment.index', [
            'equipment' => $equipment,
            'stats' => $stats,
            'branchId' => $branchId,
            'status' => $status,
            'branches' => $branches,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers add equipment.']);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'category' => 'required|in:kitchen,beverage,cleaning,furniture,other',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date',
            'next_maintenance' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($user->isManager() && $user->branch_id !== (int) $validated['branch_id']) {
            return back()->withErrors(['branch_id' => 'You can only add equipment to your own branch.']);
        }

        Equipment::create($validated);

        return redirect()->route('equipment.index')
            ->with('success', 'Equipment added successfully.');
    }

    public function update(Request $request, Equipment $equipment)
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers edit equipment.']);
        }

        if ($user->isManager() && $user->branch_id !== $equipment->branch_id) {
            return back()->withErrors(['error' => 'Forbidden.']);
        }

        $validated = $request->validate([
            'status' => 'required|in:active,maintenance_needed,out_of_service',
            'last_maintenance' => 'nullable|date',
            'next_maintenance' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $equipment->update($validated);

        return back()->with('success', 'Equipment updated successfully.');
    }

    public function destroy(Equipment $equipment)
    {
        $user = request()->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers delete equipment.']);
        }

        if ($user->isManager() && $user->branch_id !== $equipment->branch_id) {
            return back()->withErrors(['error' => 'Forbidden.']);
        }

        $equipment->delete();

        return redirect()->route('equipment.index')
            ->with('success', 'Equipment deleted.');
    }
}
