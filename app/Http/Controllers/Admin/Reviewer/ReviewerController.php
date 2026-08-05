<?php

namespace App\Http\Controllers\Admin\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminReviewerScope;
use App\Models\Unite;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ReviewerController extends Controller
{
    public function index()
    {
        $reviewers = Admin::role('reviewer')
            ->with('reviewerScopes.unite')
            ->orderBy('name')
            ->get();

        $allAdmins = Admin::whereDoesntHave('roles', fn ($q) => $q->where('name', 'reviewer'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $unites = Unite::with('department')
            ->where('status', 'active')
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'department_id']);

        return view('dashboard.admin.reviewers.index', compact('reviewers', 'allAdmins', 'unites'));
    }

    /** Promote an admin to reviewer role and optionally set their initial scope */
    public function store(Request $request)
    {
        $request->validate([
            'admin_id' => ['required', 'exists:admins,id'],
            'scope_type' => ['required', 'in:all,types,unites'],
            'types' => ['array'],
            'types.*' => ['in:stadium,hall,lounge,camp,other'],
            'unite_ids' => ['array'],
            'unite_ids.*' => ['exists:unites,id'],
        ]);

        $admin = Admin::findOrFail($request->admin_id);
        $admin->syncRoles(['reviewer']);

        $this->saveScopes($admin, $request);

        return back()->with('success', "{$admin->name} is now a reviewer.");
    }

    /** Update scope for an existing reviewer */
    public function update(Request $request, Admin $reviewer)
    {
        $request->validate([
            'scope_type' => ['required', 'in:all,types,unites'],
            'types' => ['array'],
            'types.*' => ['in:stadium,hall,lounge,camp,other'],
            'unite_ids' => ['array'],
            'unite_ids.*' => ['exists:unites,id'],
        ]);

        $this->saveScopes($reviewer, $request);

        return back()->with('success', "Scope updated for {$reviewer->name}.");
    }

    /** Remove reviewer role and all scopes */
    public function destroy(Admin $reviewer)
    {
        $reviewer->reviewerScopes()->delete();
        $reviewer->syncRoles(['viewer']); // demote to viewer rather than leave role-less

        return back()->with('success', "{$reviewer->name} removed from reviewers.");
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function saveScopes(Admin $admin, Request $request): void
    {
        // Always wipe existing scopes first
        $admin->reviewerScopes()->delete();

        if ($request->scope_type === 'all') {
            // No scope rows = unrestricted (sees everything)
            return;
        }

        if ($request->scope_type === 'types') {
            foreach ($request->types ?? [] as $type) {
                AdminReviewerScope::create([
                    'admin_id' => $admin->id,
                    'unite_type' => $type,
                    'unite_id' => null,
                ]);
            }

            return;
        }

        // scope_type === 'unites'
        foreach ($request->unite_ids ?? [] as $uniteId) {
            AdminReviewerScope::create([
                'admin_id' => $admin->id,
                'unite_type' => null,
                'unite_id' => $uniteId,
            ]);
        }
    }
}
