<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthOnboardingController extends Controller
{
    // ── Views ──────────────────────────────────────────────────────────

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegisterStep1(): View
    {
        return view('auth.register-step-1');
    }

    public function showRegisterStep2(): View
    {
        return view('auth.register-owner-step-2');
    }

    public function showRegisterStep3(): View
    {
        return view('auth.register-owner-step-3');
    }

    public function showOwnerStep2(): View
    {
        return view('auth.register-owner-step-2');
    }

    public function showOwnerStep3(): View
    {
        return view('auth.register-owner-step-3');
    }

    public function showManagerStep2(): View
    {
        return view('auth.register-manager-step-2');
    }

    public function showManagerStep3(): View
    {
        return view('auth.register-manager-step-3');
    }

    // ── API Handlers ──────────────────────────────────────────────────

    /**
     * Register Step 1 — Create a pending User record.
     *
     * POST /api/auth/register/step-1
     * Body: { full_name, email, contact_number, role }
     *
     * Creates a user with a temporary password and returns the user ID
     * so subsequent steps can reference it.
     */
    public function apiRegisterStep1(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name'       => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'unique:users,email'],
            'contact_number'  => ['required', 'string', 'max:50'],
            'role'            => ['required', 'string', 'in:owner,manager'],
        ]);

        $user = User::create([
            'name'  => $validated['full_name'],
            'email' => $validated['email'],
            'role'  => $validated['role'] === 'owner' ? User::ROLE_SUPER_ADMIN : User::ROLE_MANAGER,
            // Generate a temporary password — the user will reset it
            'password' => Hash::make(Str::random(32)),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Personal information saved. Proceed to step 2.',
            'data'    => [
                'user_id' => $user->id,
                'email'   => $user->email,
                'role'    => $validated['role'],
            ],
        ], 201);
    }

    /**
     * Register Step 2 (Owner) — Create a Branch for the owner.
     *
     * POST /api/auth/register/step-2
     * Body (API / Postman): { user_id, businesses: [{ business_name, type_of_business, business_registration, business_permit, location }] }
     * Body (web form):      { user_id, business_name, location }
     *
     * Returns the created branches.
     */
    public function apiRegisterStep2(Request $request): JsonResponse
    {
        // Accept both the array format (Postman) and flat format (web form)
        if ($request->has('businesses')) {
            $validated = $request->validate([
                'user_id' => ['required', 'exists:users,id'],
                'businesses' => ['required', 'array', 'min:1'],
                'businesses.*.business_name' => ['required', 'string', 'max:255'],
                'businesses.*.type_of_business' => ['nullable', 'string', 'max:255'],
                'businesses.*.business_registration' => ['nullable', 'string', 'max:255'],
                'businesses.*.business_permit' => ['nullable', 'string', 'max:255'],
                'businesses.*.location' => ['nullable', 'string', 'max:500'],
            ]);

            $branches = [];
            DB::transaction(function () use ($validated, &$branches) {
                foreach ($validated['businesses'] as $biz) {
                    $branches[] = Branch::create([
                        'name'     => $biz['business_name'],
                        'location' => $biz['location'] ?? '',
                        'status'   => 'active',
                    ]);
                }
            });

            return response()->json([
                'status'  => true,
                'message' => count($branches).' branch(es) created.',
                'data'    => ['branches' => $branches],
            ], 201);
        }

        // Flat format — used by the web registration form
        $validated = $request->validate([
            'user_id'       => ['required_without:email', 'exists:users,id'],
            'email'         => ['required_without:user_id', 'email', 'exists:users,email'],
            'business_name' => ['required', 'string', 'max:255'],
            'location'      => ['nullable', 'string', 'max:500'],
        ]);

        $userId = $validated['user_id'] ?? User::where('email', $validated['email'])->value('id');

        $branch = Branch::create([
            'name'     => $validated['business_name'],
            'location' => $validated['location'] ?? '',
            'status'   => 'active',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Branch created successfully.',
            'data'    => ['branch' => $branch],
        ], 201);
    }

    /**
     * Register Step 2 (Manager) — Create a Branch for the manager.
     *
     * POST /api/auth/register/manager/step-2
     * Body: { user_id, business_name, branch_location, business_owner }
     */
    public function apiRegisterManagerStep2(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'         => ['required', 'exists:users,id'],
            'business_name'   => ['required', 'string', 'max:255'],
            'branch_location' => ['required', 'string', 'max:500'],
            'business_owner'  => ['required', 'string', 'max:255'],
        ]);

        $branch = Branch::create([
            'name'     => $validated['business_name'],
            'location' => $validated['branch_location'],
            'status'   => 'active',
        ]);

        // Optionally link the manager to the branch
        User::where('id', $validated['user_id'])->update(['branch_id' => $branch->id]);

        return response()->json([
            'status'  => true,
            'message' => 'Branch created and manager assigned.',
            'data'    => ['branch' => $branch],
        ], 201);
    }

    /**
     * Register Confirm — Finalise the registration.
     *
     * POST /api/auth/register/confirm
     * Body: { user_id (or email), permit_validity, terms_accepted, legal_papers_submitted, legal_papers_secondary_submitted }
     */
    public function apiRegisterConfirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required_without:email', 'exists:users,id'],
            'email'   => ['required_without:user_id', 'email', 'exists:users,email'],
            'permit_validity'              => ['required', 'boolean'],
            'terms_accepted'               => ['required', 'boolean'],
            'legal_papers_submitted'       => ['required', 'boolean'],
            'legal_papers_secondary_submitted' => ['required', 'boolean'],
        ]);

        $userId = $validated['user_id'] ?? User::where('email', $validated['email'])->value('id');

        return response()->json([
            'status'  => true,
            'message' => 'Onboarding completed successfully.',
            'data'    => [
                'user_id' => $userId,
                'trackers' => [
                    'permit_validity' => $validated['permit_validity'] ? 'valid' : 'pending',
                    'terms_of_service' => $validated['terms_accepted'] ? 'accepted' : 'pending',
                    'legal_papers' => $validated['legal_papers_submitted'] ? 'submitted' : 'pending',
                    'legal_papers_secondary' => $validated['legal_papers_secondary_submitted'] ? 'submitted' : 'pending',
                ],
            ],
        ]);
    }
}
