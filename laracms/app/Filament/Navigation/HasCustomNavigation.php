<?php

namespace Lara\App\Filament\Navigation;

use Filament\Navigation\NavigationItem;

trait HasCustomNavigation
{
	public static function getCustomNavigation()
	{
		$rows = array();

		if(config('lara-admin.has_custom_routes')) {
			$rows[] = NavigationItem::make('Custom Report')
				->url(fn (): string => route('admin.custom-blog.index'))
				->group('Custom')
				->isActiveWhen(fn () => request()->routeIs('admin.custom-blog.index'))
				->sort(4);
		}

		return $rows;
	}
}