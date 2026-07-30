<?php

namespace Lara\App\Filament\Enums;

use Filament\Support\Contracts\HasLabel;

enum CustomNavGroup: string implements HasLabel
{
	case Custom = 'custom';

	public function getLabel(): ?string
	{
		return match ($this) {
			self::Custom => 'Custom',
		};
	}

	public function getLabelNl(): ?string
	{
		return match ($this) {
			self::Custom => 'Custom',
		};
	}

	public function getLabelEn(): ?string
	{
		return match ($this) {
			self::Custom => 'Custom',
		};
	}

	public function getIcon(): ?string
	{
		return match ($this) {
			self::Custom => 'bi-box',
		};
	}

	public static function toArray(): array
	{
		$array = [];
		foreach (self::cases() as $case) {
			$array[$case->value] = $case->value;
		}
		return $array;
	}

}
