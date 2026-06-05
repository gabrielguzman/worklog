<?php

namespace Database\Factories;

use App\Models\FocusSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class FocusSessionFactory extends Factory
{
    protected $model = FocusSession::class;

    public function definition(): array
    {
        $started  = $this->faker->dateTimeBetween('-7 days', '-1 hour');
        $duration = $this->faker->randomElement([25, 25, 25, 50, 15]);
        $ended    = (clone $started)->modify("+{$duration} minutes");

        return [
            'duration_minutes' => $duration,
            'started_at'       => $started,
            'ended_at'         => $ended,
            'notes'            => $this->faker->optional(0.4)->sentence(),
            'status'           => 'completed',
        ];
    }
}
