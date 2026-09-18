<?php

namespace Lara\App\Filament\Resources\Videos;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Video;

class VideoResource extends BaseResource
{
	protected static ?string $model = Video::class;

	protected static bool $shouldRegisterNavigation = true;

	public static function getPages(): array
	{
		return [
			'index'   => Pages\ListVideos::route('/'),
			'create'  => Pages\CreateVideo::route('/create'),
			'reorder' => Pages\ReorderVideos::route('/reorder'),
			'view'    => Pages\ViewVideo::route('/{record}'),
			'edit'    => Pages\EditVideo::route('/{record}/edit'),
		];
	}

}
