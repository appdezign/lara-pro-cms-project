<?php

namespace Lara\App\Filament\Resources\Teams;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Team;

class TeamResource extends BaseResource
{
	protected static ?string $model = Team::class;

	protected static bool $shouldRegisterNavigation = true;

	public static function getPages(): array
	{
		return [
			'index'   => Pages\ListTeams::route('/'),
			'create'  => Pages\CreateTeam::route('/create'),
			'reorder' => Pages\ReorderTeams::route('/reorder'),
			'view'    => Pages\ViewTeam::route('/{record}'),
			'edit'    => Pages\EditTeam::route('/{record}/edit'),
		];
	}

}
