<?php

namespace Lara\App\Filament\Resources\Testimonials;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Testimonial;

class TestimonialResource extends BaseResource
{
	protected static ?string $model = Testimonial::class;

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
