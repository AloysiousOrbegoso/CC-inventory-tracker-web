<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesBranchAccess;
use App\Models\Branch;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoticesController extends Controller
{
    use AuthorizesBranchAccess;

    public function index(): View
    {
        $user = auth()->user();
        $isBranchScoped = ! $user->isSuperAdmin();
        $branchId = $isBranchScoped ? $user->branch_id : null;

        $notices = Notice::with('branch', 'poster')
            ->when($isBranchScoped, fn ($q) => $q->where(fn ($q2) => $q2->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->latest()
            ->get();

        $branches = Branch::when($isBranchScoped, fn ($q) => $q->where('id', $branchId))->orderBy('name')->get();

        return view('notices.index', compact('notices', 'branches'));
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        if (! $user->isSuperAdmin()) {
            $validated['branch_id'] = $user->branch_id;
        }

        $validated['posted_by'] = $user->id;

        $notice = Notice::create($validated);

        return response()->json(['success' => true, 'notice' => $notice->load('branch', 'poster')], 201);
    }

    public function destroy(Notice $notice): JsonResponse
    {
        $this->authorizeBranchOrCompanyWide($notice->branch_id);

        $notice->delete();

        return response()->json(['success' => true]);
    }
}
