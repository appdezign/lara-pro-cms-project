<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Blog;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class BlogFactory extends Factory
{
    protected ?string $resourceSlug = 'blogs';

    use HasLaraFactory;

    protected $model = Blog::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
