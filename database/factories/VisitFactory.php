<?php

namespace Database\Factories;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Visit;
use donatj\UserAgent\Browsers;
use donatj\UserAgent\Platforms;
use Illuminate\Database\Eloquent\Factories\Factory;
use ReflectionClass;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'country' => $this->faker->countryCode(),
            'browser' => function () {
                $reflectionClass = new ReflectionClass(Browsers::class);

                return $this->faker->randomElement($reflectionClass->getConstants());
            },
            'platform' => function () {
                $reflectionClass = new ReflectionClass(Platforms::class);

                return $this->faker->randomElement($reflectionClass->getConstants());
            },
            'ip' => $this->faker->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-1 year'),

            'link_id' => Link::factory(),

            'domain_id' => Domain::inRandomOrder()->first()->id,
        ];
    }
}
