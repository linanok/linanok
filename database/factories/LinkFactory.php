<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        return [
            'original_url' => $this->faker->url(),
            'short_path' => $this->faker->firstName(),
            'forward_query_parameters' => $this->faker->boolean(),
            'send_ref_query_parameter' => $this->faker->boolean(),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'description' => $this->faker->text(),
        ];
    }
}
