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
            'index' => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'reorder' => Pages\ReorderGalleries::route('/reorder'),
            'view' => Pages\ViewGallery::route('/{record}'),
            'edit' => Pages\EditGallery::route('/{record}/edit'),
        ];
    }
}
