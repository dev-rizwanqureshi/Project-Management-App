<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'industry' => fake()->randomElement(['Software', 'Consulting', 'Education', 'Healthcare']),
            'team_size' => fake()->randomElement(['1-10', '11-50', '51-200', '201-500']),
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['California', 'Florida', 'New York', 'Texas']),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'timezone' => fake()->timezone(),
            'description' => fake()->sentence(12),
            'logo' => null,
            'trial_ends_at' => now()->addDays(14),
        ];
    }
}
