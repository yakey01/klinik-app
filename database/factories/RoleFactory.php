<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = [
            'admin' => 'Administrator',
            'manajer' => 'Manajer',
            'bendahara' => 'Bendahara',
            'petugas' => 'Petugas',
            'dokter' => 'Dokter',
            'paramedis' => 'Paramedis',
            'non_paramedis' => 'Non Paramedis'
        ];

        $roleName = $this->faker->randomElement(array_keys($roles));

        return [
            'name' => $roleName,
            'display_name' => $roles[$roleName],
            'permissions' => ['basic_access'],
        ];
    }

    /**
     * Create an admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
            'display_name' => 'Administrator',
            'permissions' => ['*'],
        ]);
    }

    /**
     * Create a petugas role.
     */
    public function petugas(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'petugas',
            'display_name' => 'Petugas',
            'permissions' => ['basic_access', 'data_input'],
        ]);
    }

    /**
     * Create a paramedis role.
     */
    public function paramedis(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'paramedis',
            'display_name' => 'Paramedis',
            'permissions' => ['basic_access', 'medical_procedures'],
        ]);
    }
}
