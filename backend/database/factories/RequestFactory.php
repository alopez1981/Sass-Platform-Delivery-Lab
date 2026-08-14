<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Organization;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Request>
 */
class RequestFactory extends Factory
{
    protected $model = Request::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'created_by' => fn (array $attributes) => User::factory()->create([
                'organization_id' => $attributes['organization_id'],
            ])->id,
            'assigned_to' => null,
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'status' => RequestStatus::Draft,
        ];
    }
}
