<?php

namespace Lara\App\Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

use Lara\App\Models\Blog;
use Lara\Common\Models\User;

use Carbon\Carbon;

/**
 * @extends Factory<Model>
 */
#[UseModel(Blog::class)]
class BlogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

	    $superAdminId = User::role('superadmin')->value('id');
		$locale = config('app.locale');

        return [
	        'user_id' =>  $superAdminId,
	        'language' =>  $locale,
	        'title' =>  $this->faker->sentence(5),
	        'lead' =>  $this->faker->paragraph(1),
	        'body' =>  $this->faker->paragraph(2),
	        'publish' =>  1,
	        'publish_from' =>  Carbon::now(),
        ];
    }
}
