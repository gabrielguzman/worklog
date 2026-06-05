<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    private static array $names = [
        'backend', 'frontend', 'bug', 'feature', 'refactor',
        'deploy', 'urgente', 'revisión', 'docs', 'tests',
        'bd', 'api', 'performance', 'seguridad', 'devops',
    ];

    public function definition(): array
    {
        return [
            'name'  => $this->faker->unique()->randomElement(self::$names),
            'color' => $this->faker->randomElement(['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6']),
        ];
    }
}
