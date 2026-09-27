<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Video;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class VideoFactory extends Factory
{
    protected ?string $resourceSlug = 'videos';

    use HasLaraFactory;

    protected $model = Video::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
