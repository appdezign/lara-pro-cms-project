<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Testimonial;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class TestimonialFactory extends Factory
{
    protected ?string $resourceSlug = 'testimonials';

    use HasLaraFactory;

    protected $model = Testimonial::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
