<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Service;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class ServiceFactory extends Factory
{
    protected ?string $resourceSlug = 'services';

    use HasLaraFactory;

    protected $model = Service::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
