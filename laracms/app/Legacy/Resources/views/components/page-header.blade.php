{{--
    Page header for custom (non-Livewire) admin pages.

    Same markup as <x-filament-panels::header>, which can only be used inside a Livewire page
    (it reads render hook scopes from $this and expects Filament Action objects).
--}}
@props([
    'heading',
    'subheading' => null,
    'breadcrumbs' => [],
])

<header @class([
    'fi-header',
    'fi-header-has-breadcrumbs' => $breadcrumbs,
    'fi-header-has-subheading' => filled($subheading),
])>
    <div>
        @if ($breadcrumbs)
            <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs"/>
        @endif

        <h1 class="fi-header-heading">
            {{ $heading }}
        </h1>

        @if (filled($subheading))
            <p class="fi-header-subheading">
                {{ $subheading }}
            </p>
        @endif
    </div>

    @isset($actions)
        <div class="fi-header-actions-ctn">
            <div class="fi-ac">
                {{ $actions }}
            </div>
        </div>
    @endisset
</header>
