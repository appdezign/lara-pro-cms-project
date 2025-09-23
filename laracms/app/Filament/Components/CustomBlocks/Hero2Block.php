<?php

namespace Lara\App\Filament\Components\CustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;

class Hero2Block extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'hero2';
    }

    public static function getLabel(): string
    {
        return 'Hero 2';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the hero 2 block')
	        ->schema([
		        TextInput::make('heading')
			        ->required(),
		        TextInput::make('subheading'),
	        ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('lara-app::components.custom-blocks.hero2.preview', [
	        'heading' => $config['heading'],
	        'subheading' => $config['subheading'] ?? 'Default subheading',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('lara-app::components.custom-blocks.hero2.index', [
	        'heading' => $config['heading'],
	        'subheading' => $config['subheading'] ?? 'Default subheading',
        ])->render();
    }
}
