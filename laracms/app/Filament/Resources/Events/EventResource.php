<?php

namespace Lara\App\Filament\Resources\Events;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Event;

class EventResource extends BaseResource
{
    protected static ?string $model = Event::class;

    protected static bool $shouldRegisterNavigation = true;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'reorder' => Pages\ReorderEvents::route('/reorder'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
