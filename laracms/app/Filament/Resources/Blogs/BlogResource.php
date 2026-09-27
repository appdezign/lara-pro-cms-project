<?php

namespace Lara\App\Filament\Resources\Blogs;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Blog;

class BlogResource extends BaseResource
{
    protected static ?string $model = Blog::class;

    protected static bool $shouldRegisterNavigation = true;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'reorder' => Pages\ReorderBlogs::route('/reorder'),
            'view' => Pages\ViewBlog::route('/{record}'),
            'edit' => Pages\EditBlog::route('/{record}/edit'),
        ];
    }
}
