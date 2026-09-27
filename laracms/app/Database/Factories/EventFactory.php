<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Event;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class EventFactory extends Factory
{
    protected ?string $resourceSlug = 'events';

    use HasLaraFactory;

    protected $model = Event::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
