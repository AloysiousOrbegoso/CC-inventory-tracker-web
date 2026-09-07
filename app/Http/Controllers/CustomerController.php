<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerFeedback;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();
        $branchId = $request->query('branch_id');
        $tier = $request->query('tier');
        $search = $request->query('search');

        $query = Customer::with('branch');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($isManager) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($tier) {
            $query->where('tier', $tier);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(20);

        // Stats
        $statsQuery = Customer::query();
        if ($isManager) {
            $statsQuery->where('branch_id', $user->branch_id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'bronze' => (clone $statsQuery)->where('tier', 'bronze')->count(),
            'silver' => (clone $statsQuery)->where('tier', 'silver')->count(),
            'gold' => (clone $statsQuery)->where('tier', 'gold')->count(),
            'platinum' => (clone $statsQuery)->where('tier', 'platinum')->count(),
            'totalSpent' => (clone $statsQuery)->sum('total_spent'),
        ];

        $branches = $isManager
            ? Branch::where('id', $user->branch_id)->get()
            : Branch::where('status', 'active')->orderBy('name')->get();

        return view('customers.index', [
            'customers' => $customers,
            'stats' => $stats,
            'branchId' => $branchId,
            'tier' => $tier,
            'search' => $search,
            'branches' => $branches,
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load('branch', 'feedback');

        // Get feedback stats
        $feedbackStats = [
            'avg_rating' => $customer->feedback->avg('rating') ?? 0,
            'total_reviews' => $customer->feedback->count(),
            'by_category' => $customer->feedback->groupBy('category')
                ->map(fn($reviews) => [
                    'count' => $reviews->count(),
                    'avg' => $reviews->avg('rating'),
                ]),
        ];

        return view('customers.show', [
            'customer' => $customer,
            'feedbackStats' => $feedbackStats,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isManager()) {
            return back()->withErrors(['message' => 'Forbidden. Managers add customers.']);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
        ]);

        if ($user->isManager() && $user->branch_id !== (int) $validated['branch_id']) {
            return back()->withErrors(['branch_id' => 'You can only add customers to your own branch.']);
        }

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer added successfully.');
    }

    public function storeFeedback(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'category' => 'nullable|string|max:100',
            'comment' => 'nullable|string|max:1000',
        ]);

        CustomerFeedback::create([
            'customer_id' => $customer->id,
            'branch_id' => $customer->branch_id,
            'rating' => $validated['rating'],
            'category' => $validated['category'] ?? null,
            'comment' => $validated['comment'] ?? null,
        ]);

        // Award loyalty points for feedback
        $customer->addLoyaltyPoints(10);

        return back()->with('success', 'Feedback recorded. Customer earned 10 loyalty points!');
    }

    public function addPoints(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $customer->addLoyaltyPoints($validated['points']);

        return back()->with('success', $validated['points'] . ' loyalty points added.');
    }
}
