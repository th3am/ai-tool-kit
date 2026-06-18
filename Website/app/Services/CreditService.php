<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * Credit cost per tool type.
     */
    protected array $costs = [
        'mindmap'       => 15,
        'audio'         => 20,
        'video-animation' => 30,
        'presentation'  => 25,
        'video-explainer' => 40,
        'lecture'       => 35,
        'quiz'          => 10,
    ];

    /**
     * Get the credit cost for a tool type.
     */
    public function costFor(string $toolType): int
    {
        return $this->costs[$toolType] ?? 10;
    }

    /**
     * Check if the user has enough credits for a tool.
     */
    public function check(User $user, string $toolType): bool
    {
        // Admins always have unlimited credits
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasCredits($this->costFor($toolType));
    }

    /**
     * Deduct credits from user for a tool usage.
     * Returns true on success, false if insufficient credits.
     */
    public function deduct(User $user, string $toolType): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $cost = $this->costFor($toolType);

        $success = $user->deductCredits($cost);

        if ($success) {
            Log::info("CreditService: Deducted {$cost} credits from User#{$user->id} for tool '{$toolType}'. Remaining: {$user->fresh()->credits}");
        } else {
            Log::warning("CreditService: Insufficient credits for User#{$user->id} — tool '{$toolType}' requires {$cost} credits.");
        }

        return $success;
    }

    /**
     * Refund credits to user (e.g., on job failure or cancellation).
     */
    public function refund(User $user, string $toolType): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $cost = $this->costFor($toolType);
        $user->refundCredits($cost);

        Log::info("CreditService: Refunded {$cost} credits to User#{$user->id} for tool '{$toolType}'.");
    }

    /**
     * Get all tool costs map.
     */
    public function allCosts(): array
    {
        return $this->costs;
    }
}
