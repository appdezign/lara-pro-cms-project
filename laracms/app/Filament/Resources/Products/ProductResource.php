<?php

namespace Lara\App\Filament\Resources\Products;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Product;

class ProductResource extends BaseResource
{
	protected static ?string $model = Product::class;

	protected static bool $shouldRegisterNavigation = false;

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
