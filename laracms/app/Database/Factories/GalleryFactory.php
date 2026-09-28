<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Gallery;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class GalleryFactory extends Factory
{
    protected ?string $resourceSlug = 'galleries';

    use HasLaraFactory;

    protected $model = Gallery::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
