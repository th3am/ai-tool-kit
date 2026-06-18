<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('plan')->where('role', '!=', 'admin');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('whatsapp_number', 'like', "%{$search}%");
            });
        }

        if ($planId = $request->get('plan_id')) {
            $query->where('plan_id', $planId);
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(20)->withQueryString();
        $plans = SubscriptionPlan::orderBy('sort_order')->get();

        return view('admin.users.index', compact('users', 'plans'));
    }

    public function show(User $user)
    {
        $user->load(['plan', 'toolJobs' => fn($q) => $q->latest()->take(20)]);
        $plans = SubscriptionPlan::orderBy('sort_order')->get();

        return view('admin.users.show', compact('user', 'plans'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'nullable|email|unique:users,email,' . $user->id,
            'role'           => 'required|in:student,lecturer,admin',
            'plan_id'        => 'nullable|exists:subscription_plans,id',
            'credits'        => 'required|integer|min:0',
            'plan_expires_at'=> 'nullable|date',
        ]);

        $user->update($request->only([
            'name', 'email', 'role', 'plan_id', 'credits', 'plan_expires_at',
        ]));

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function addCredits(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:10000',
            'reason' => 'nullable|string|max:255',
        ]);

        $user->increment('credits', $request->amount);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "Added {$request->amount} credits to {$user->name}.");
    }

    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Cannot delete an admin user.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
