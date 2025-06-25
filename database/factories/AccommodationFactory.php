<?php

namespace Database\Factories;

use App\Models\Accommodation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Accommodation>
 */
class AccommodationFactory extends Factory
{
  /**
   * The name of the factory's corresponding model.
   *
   * @var string
   */
  protected $model = Accommodation::class;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'apidae_id' => 'APIDAE_' . $this->faker->unique()->numberBetween(1000, 9999),
      'name' => $this->faker->company() . ' - ' . $this->faker->randomElement(['Hôtel', 'Gîte', 'Chambre d\'hôte', 'Camping', 'Résidence']),
      'city' => $this->faker->city(),
      'email' => $this->faker->optional(0.8)->email(),
      'status' => $this->faker->randomElement(['pending', 'active', 'inactive']),
    ];
  }

  /**
   * Indicate that the accommodation is active.
   */
  public function active(): static
  {
    return $this->state(fn(array $attributes) => [
      'status' => 'active',
    ]);
  }

  /**
   * Indicate that the accommodation is pending.
   */
  public function pending(): static
  {
    return $this->state(fn(array $attributes) => [
      'status' => 'pending',
    ]);
  }

  /**
   * Indicate that the accommodation is inactive.
   */
  public function inactive(): static
  {
    return $this->state(fn(array $attributes) => [
      'status' => 'inactive',
    ]);
  }
}
