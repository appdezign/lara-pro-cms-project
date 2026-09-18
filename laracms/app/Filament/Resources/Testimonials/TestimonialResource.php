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
			'index'   => Pages\ListTestimonials::route('/'),
			'create'  => Pages\CreateTestimonial::route('/create'),
			'reorder' => Pages\ReorderTestimonials::route('/reorder'),
			'view'    => Pages\ViewTestimonial::route('/{record}'),
			'edit'    => Pages\EditTestimonial::route('/{record}/edit'),
		];
	}

}
