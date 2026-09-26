<?php

namespace Lara\App\Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;
use Exception;

use Lara\App\Models\Portfolio;

class PortfolioFactory extends Factory
{

	protected ?string $resourceSlug = 'portfolios';

	use HasLaraFactory;

	protected $model = Portfolio::class;

    /**
	 * @return array
	 * @throws Exception
     */
    public function definition(): array
    {
		return $this->generateContent($this->resourceSlug);
    }
}
