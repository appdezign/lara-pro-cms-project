<?php

namespace Lara\App\Filament\Resources\Contactforms;

use Lara\Admin\Resources\BaseForm\BaseFormResource;
use Lara\App\Models\Contactform;

class ContactformResource extends BaseFormResource
{
    protected static ?string $model = Contactform::class;

    protected static bool $shouldRegisterNavigation = true;

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRecords::route('/'),
            'view'   => Pages\ViewRecord::route('/{record}'),
        ];
    }

}
