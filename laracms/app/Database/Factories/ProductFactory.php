<?php

namespace Lara\App\Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;
use Exception;

use Lara\App\Models\Product;

class ProductFactory extends Factory
{

	protected ?string $resourceSlug = 'products';

	use HasLaraFactory;

	protected $model = Product::class;

    /**
	 * @return array
	 * @throws Exception
     */
    public function definition(): array
    {
		return $this->generateContent($this->resourceSlug);
    }
}
