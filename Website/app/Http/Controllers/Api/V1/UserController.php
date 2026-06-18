<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="2. User",
 *     description="User Management"
 * )
 */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/user",
     *     tags={"V1 User"},
     *     summary="Get User Info",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User Details",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function show(Request $request)
    {
        return response()->json($this->userPayload($request->user()));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user",
     *     tags={"V1 User"},
     *     summary="Update User Settings",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="whatsapp_number", type="string"),
     *                 @OA\Property(property="country_code", type="string"),
     *                 @OA\Property(property="country_name", type="string"),
     *                 @OA\Property(property="avatar", type="string", format="binary", description="User avatar image")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated Successfully",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'whatsapp_number' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:5',
            'country_name' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = url('storage/' . $path);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            ...$this->userPayload($user->fresh('plan')),
        ]);
    }

    public function plans()
    {
        return response()->json([
            'data' => SubscriptionPlan::active()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (SubscriptionPlan $plan) => $this->planPayload($plan))
                ->values(),
        ]);
    }

    private function userPayload($user): array
    {
        $user->loadMissing('plan');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp_number' => $user->whatsapp_number,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar,
            'role' => $user->role,
            'credits' => (int) ($user->credits ?? 0),
            'credits_used' => (int) ($user->credits_used ?? 0),
            'plan_id' => $user->plan_id,
            'plan_expires_at' => optional($user->plan_expires_at)->toIso8601String(),
            'current_plan' => $user->plan ? $this->planPayload($user->plan) : null,
            'upgrade_plans' => SubscriptionPlan::active()
                ->where(function ($query) use ($user) {
                    $query->whereNull('id');
                    if ($user->plan_id) {
                        $query->orWhere('id', '!=', $user->plan_id);
                    } else {
                        $query->orWhereNotNull('id');
                    }
                })
                ->orderBy('sort_order')
                ->get()
                ->map(fn (SubscriptionPlan $plan) => $this->planPayload($plan))
                ->values(),
        ];
    }

    private function planPayload(SubscriptionPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'price' => (float) $plan->price,
            'credits' => (int) $plan->credits,
            'features' => $plan->features ?? [],
            'color' => $plan->color,
            'description' => $plan->description,
        ];
    }
}
