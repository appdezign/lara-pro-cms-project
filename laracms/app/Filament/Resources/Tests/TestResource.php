<?php

namespace Lara\App\Filament\Resources\Tests;

use App\Models\Test;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Lara\App\Filament\Resources\Tests\Pages\CreateTest;
use Lara\App\Filament\Resources\Tests\Pages\EditTest;
use Lara\App\Filament\Resources\Tests\Pages\ListTests;
use Lara\App\Filament\Resources\Tests\Schemas\TestForm;
use Lara\App\Filament\Resources\Tests\Tables\TestsTable;

class TestResource extends Resource
{
    protected static ?string $model = Test::class;

	protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTests::route('/'),
            'create' => CreateTest::route('/create'),
            'edit' => EditTest::route('/{record}/edit'),
        ];
    }
}
