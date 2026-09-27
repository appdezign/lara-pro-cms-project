<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Location;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class LocationFactory extends Factory
{
    protected ?string $resourceSlug = 'locations';

    use HasLaraFactory;

    protected $model = Location::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
