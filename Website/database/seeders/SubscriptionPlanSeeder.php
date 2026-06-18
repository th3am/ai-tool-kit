<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'        => 'Free',
                'slug'        => 'free',
                'price'       => 0.00,
                'credits'     => 50,
                'color'       => 'slate',
                'description' => 'Get started with basic AI tools.',
                'is_active'   => true,
                'sort_order'  => 1,
                'features'    => [
                    'Mind Map Generator',
                    'Quiz Generator (up to 10 questions)',
                    'Audio Narration',
                    '50 credits / month',
                    'Email support',
                ],
            ],
            [
                'name'        => 'Pro',
                'slug'        => 'pro',
                'price'       => 19.99,
                'credits'     => 500,
                'color'       => 'indigo',
                'description' => 'Full access to all AI tools for professionals.',
                'is_active'   => true,
                'sort_order'  => 2,
                'features'    => [
                    'All Free features',
                    'PowerPoint Generator',
                    'Video Explainer (HD)',
                    'Lecture Explainer Video',
                    '2D Animation Video',
                    '500 credits / month',
                    'Priority support',
                ],
            ],
            [
                'name'        => 'Enterprise',
                'slug'        => 'enterprise',
                'price'       => 79.99,
                'credits'     => 2000,
                'color'       => 'purple',
                'description' => 'Unlimited power for teams and institutions.',
                'is_active'   => true,
                'sort_order'  => 3,
                'features'    => [
                    'All Pro features',
                    'Unlimited team members',
                    'Custom branding',
                    'API access',
                    '2000 credits / month',
                    'Dedicated account manager',
                    'SLA guarantee',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
