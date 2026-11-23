<?php

namespace Database\Factories;

use App\Models\PluggyItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PluggyItem> */
class PluggyItemFactory extends Factory
{
    protected $model = PluggyItem::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pluggy_item_id' => fake()->uuid(),
            'last_event' => 'item/created',
        ];
    }
}
