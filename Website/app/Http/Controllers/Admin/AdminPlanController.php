<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class AdminPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::withCount('users')->orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        SubscriptionPlan::create($data);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(SubscriptionPlan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $data = $this->validated($request);

        $plan->update($data);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $plan)
    {
        if ($plan->users()->exists()) {
            return back()->with('error', 'Cannot delete a plan with active subscribers. Deactivate it instead.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted.');
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => 'required|string|max:100|alpha_dash',
            'price'       => 'required|numeric|min:0',
            'credits'     => 'required|integer|min:0',
            'color'       => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer|min:0',
            'features'    => 'nullable|string', // JSON string from textarea
        ]);

        $features = [];
        if ($request->filled('features')) {
            // Accept newline-separated or JSON
            $raw = $request->features;
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $features = $decoded;
            } else {
                $features = array_filter(array_map('trim', explode("\n", $raw)));
            }
        }

        return [
            'name'        => $request->name,
            'slug'        => $request->slug,
            'price'       => $request->price,
            'credits'     => $request->credits,
            'color'       => $request->color,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
            'sort_order'  => $request->sort_order ?? 0,
            'features'    => $features,
        ];
    }
}
