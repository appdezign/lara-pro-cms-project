<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Product;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class ProductFactory extends Factory
{
    protected ?string $resourceSlug = 'products';

    use HasLaraFactory;

    protected $model = Product::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
