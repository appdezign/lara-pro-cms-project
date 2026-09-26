<?php

namespace Lara\App\Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;
use Exception;

use Lara\App\Models\Video;

class VideoFactory extends Factory
{

	protected ?string $resourceSlug = 'videos';

	use HasLaraFactory;

	protected $model = Video::class;

    /**
	 * @return array
	 * @throws Exception
     */
    public function definition(): array
    {
		return $this->generateContent($this->resourceSlug);
    }
}
