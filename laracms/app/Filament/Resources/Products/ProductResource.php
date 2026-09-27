<?php

namespace Lara\App\Filament\Resources\Products;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Product;

class ProductResource extends BaseResource
{
    protected static ?string $model = Product::class;

    protected static bool $shouldRegisterNavigation = true;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'reorder' => Pages\ReorderProducts::route('/reorder'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
