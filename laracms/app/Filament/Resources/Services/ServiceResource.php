<?php

namespace Lara\App\Filament\Resources\Services;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Service;

class ServiceResource extends BaseResource
{
	protected static ?string $model = Service::class;

	protected static bool $shouldRegisterNavigation = true;

	public static function getPages(): array
	{
		return [
			'index'   => Pages\ListServices::route('/'),
			'create'  => Pages\CreateService::route('/create'),
			'reorder' => Pages\ReorderServices::route('/reorder'),
			'view'    => Pages\ViewService::route('/{record}'),
			'edit'    => Pages\EditService::route('/{record}/edit'),
		];
	}

}
