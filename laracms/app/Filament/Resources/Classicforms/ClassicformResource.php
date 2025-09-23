<?php

namespace Lara\App\Filament\Resources\Classicforms;

use Lara\Admin\Resources\BaseForm\BaseFormResource;
use Lara\App\Models\Classicform;

class ClassicformResource extends BaseFormResource
{
	protected static ?string $model = Classicform::class;

	protected static bool $shouldRegisterNavigation = true;

	public static function getPages(): array
	{
		return [
			'index'   => Pages\ListRecords::route('/'),
			'view'    => Pages\ViewRecord::route('/{record}'),
		];
	}

}
