<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    private static array $names = [
        'WorkLog App', 'API REST', 'Dashboard Admin', 'Migración BD',
        'Refactor Auth', 'CI/CD Pipeline', 'Documentación', 'Mobile App',
    ];

    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->randomElement(self::$names),
            'color'       => $this->faker->randomElement(['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899']),
            'description' => $this->faker->sentence(),
            'is_active'   => true,
        ];
    }
}
