<?php

namespace Lara\App\Legacy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Legacy\Models\CustomBlog;
use Lara\Common\Models\User;

/**
 * @extends Factory<CustomBlog>
 */
class CustomBlogFactory extends Factory
{
    protected $model = CustomBlog::class;

    /**
     * The slug is left to Sluggable, which generates it from the title.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::query()->value('id'),
            'language' => 'nl',
            'title' => fake()->sentence(3),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'publish' => true,
        ];
    }
}
