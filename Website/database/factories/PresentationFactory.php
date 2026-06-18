<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Presentation>
 */
class PresentationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'topic' => $this->faker->sentence(3),
            'content' => [
                'slides' => [
                    ['title' => 'Title Slide', 'content' => 'Content'],
                ]
            ],
        ];
    }
}
