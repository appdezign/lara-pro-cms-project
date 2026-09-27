<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Team;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class TeamFactory extends Factory
{
    protected ?string $resourceSlug = 'teams';

    use HasLaraFactory;

    protected $model = Team::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
