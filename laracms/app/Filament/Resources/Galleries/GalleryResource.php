<?php

namespace Lara\App\Filament\Resources\Galleries;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Gallery;

class GalleryResource extends BaseResource
{
	protected static ?string $model = Gallery::class;

	protected static bool $shouldRegisterNavigation = true;

	public static function getPages(): array
	{
		return [
			'index'   => Pages\ListRecords::route('/'),
			'create'  => Pages\CreateRecord::route('/create'),
			'reorder' => Pages\ReorderRecords::route('/reorder'),
			'view'    => Pages\ViewRecord::route('/{record}'),
			'edit'    => Pages\EditRecord::route('/{record}/edit'),
		];
	}

}
