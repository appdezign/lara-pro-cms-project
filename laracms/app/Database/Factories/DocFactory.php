<?php

namespace Lara\App\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lara\App\Models\Doc;
use Lara\Common\Database\Factories\Concerns\HasLaraFactory;

class DocFactory extends Factory
{
    protected ?string $resourceSlug = 'docs';

    use HasLaraFactory;

    protected $model = Doc::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return $this->generateContent($this->resourceSlug);
    }
}
