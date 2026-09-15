<?php
use Composer\InstalledVersions;
$laraVersion = InstalledVersions::getPrettyVersion('appdezign/lara-pro-cms');
?>

@props([
    'heading' => null,
    'logo' => true,
    'subheading' => null,
])

<header class="fi-simple-header">
	<img alt="Lara 10" src="/assets/filament/img/lara10-logo.png" style="height: 3rem;" class="fi-logo">
	<div class="absolute w-100 bottom-0 py-2 text-sm  text-gray-500 text-center">v{{ $laraVersion }}</div>
</header>
