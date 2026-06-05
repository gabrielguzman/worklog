<?php

namespace Database\Factories;

use App\Models\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntryFactory extends Factory
{
    protected $model = Entry::class;

    private static array $types    = ['general', 'reunion', 'deploy', 'code_review', 'investigacion', 'planificacion'];
    private static array $titles   = [
        'Revisión de código del módulo de auth',
        'Deploy a staging del feature de pagos',
        'Reunión de planificación del sprint',
        'Investigación sobre Redis para caché',
        'Fix del bug en el formulario de registro',
        'Refactor del servicio de notificaciones',
        'Configuración de CI/CD en GitHub Actions',
        'Code review de PR #42 — Dashboard',
        'Actualización de dependencias npm',
        'Documentación del endpoint de usuarios',
        'Testing de integración con API externa',
        'Optimización de queries en el dashboard',
        'Setup del entorno de desarrollo local',
        'Análisis de performance con Lighthouse',
        'Reunión con cliente — demo del prototipo',
    ];

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'title'      => $this->faker->randomElement(self::$titles),
            'content'    => $this->faker->paragraphs(rand(2, 4), true),
            'type'       => $this->faker->randomElement(self::$types),
            'entry_date' => $date->format('Y-m-d'),
            'entry_time' => $date->format('H:i:s'),
            'is_pinned'  => $this->faker->boolean(10),
        ];
    }
}
