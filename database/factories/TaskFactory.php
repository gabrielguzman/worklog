<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    private static array $titles = [
        'Implementar autenticación JWT',
        'Crear tests unitarios para UserService',
        'Migrar base de datos a producción',
        'Revisar y mergear PR del dashboard',
        'Actualizar documentación de la API',
        'Configurar variables de entorno en staging',
        'Optimizar consultas de reportes',
        'Agregar validación al formulario de contacto',
        'Integrar pasarela de pagos',
        'Resolver conflictos de merge en rama feature',
        'Subir assets a CDN',
        'Revisar logs de errores en producción',
        'Crear componente de tabla reutilizable',
        'Setup de monitoreo con Sentry',
        'Escribir seed de datos de prueba',
    ];

    public function definition(): array
    {
        $status   = $this->faker->randomElement(['pending', 'pending', 'pending', 'in_progress', 'done']);
        $priority = $this->faker->randomElement(['low', 'medium', 'medium', 'high', 'urgent']);

        return [
            'title'        => $this->faker->randomElement(self::$titles),
            'description'  => $this->faker->optional(0.6)->sentence(),
            'priority'     => $priority,
            'status'       => $status,
            'due_date'     => $this->faker->optional(0.5)->dateTimeBetween('now', '+14 days')?->format('Y-m-d'),
            'completed_at' => $status === 'done' ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
            'sort_order'   => $this->faker->numberBetween(0, 100),
        ];
    }
}
