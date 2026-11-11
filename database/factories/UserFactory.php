<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'namalengkap' => fake()->name(),
            'password' => static::$password ??= 'password',
            'role' => 'siswa',
            'gemini_api_key' => null,
        ];
    }

    public function guru(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'guru',
        ]);
    }

    public function siswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'siswa',
        ]);
    }
}
