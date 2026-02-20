<?php

namespace Lara\App\Filament\Components\Mason;

use Lara\App\Filament\Components\Mason\Section;

class BrickCollection
{
	public static function make(): array
	{
		return [
			Section::class,
		];
	}
}
