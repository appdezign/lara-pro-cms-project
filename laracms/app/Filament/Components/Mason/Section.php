<?php

namespace Lara\App\Filament\Components\Mason;

use Awcodes\Mason\Brick;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Throwable;

class Section extends Brick
{
	public static function getId(): string
	{
		return 'section';
	}

	public static function getIcon(): string | Heroicon | Htmlable | null
	{
		return new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 20h.01M4 20h.01M8 20h.01M12 20h.01M16 20h.01M20 4h.01M4 4h.01M8 4h.01M12 4h.01M16 4v.01M4 9a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/></svg>');
	}

	/**
	 * @throws Throwable
	 */
	public static function toHtml(array $config, ?array $data = null): ?string
	{
		return view('lara-app::components.mason.bricks.section', [
			'text' => $config['text'] ?? null,
			'image' => $config['image'] ?? null,
			'image_position' => $config['image_position'] ?? null,
		])->render();
	}

	public static function configureBrickAction(Action $action): Action
	{
		return $action
			->slideOver()
			->schema([
				FileUpload::make('image')
					->inlineLabel(false),
				RichEditor::make('text')
					->inlineLabel(false),
				ToggleButtons::make('image_position')
					->options([
						'start' => 'Start',
						'end' => 'End',
					])
					->grouped()
					->inlineLabel(false),
			]);
	}
}
