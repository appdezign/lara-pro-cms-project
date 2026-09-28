<?php

namespace Lara\App\Filament\Resources\Portfolios;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Portfolio;

class PortfolioResource extends BaseResource
{
    protected static ?string $model = Portfolio::class;

    protected static bool $shouldRegisterNavigation = true;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortfolios::route('/'),
            'create' => Pages\CreatePortfolio::route('/create'),
            'reorder' => Pages\ReorderPortfolios::route('/reorder'),
            'view' => Pages\ViewPortfolio::route('/{record}'),
            'edit' => Pages\EditPortfolio::route('/{record}/edit'),
        ];
    }
}
