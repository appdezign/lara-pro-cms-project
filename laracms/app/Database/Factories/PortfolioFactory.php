<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Portfolio;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class PortfolioFactory extends Factory
{
    protected ?string $resourceSlug = 'portfolios';

    use HasLaraFactory;

    protected $model = Portfolio::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
