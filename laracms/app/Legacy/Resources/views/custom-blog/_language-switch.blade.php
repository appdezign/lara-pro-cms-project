{{--
    Content language switch for the custom blog index.

    The panel's own switch only works on entity resource routes. Choosing a language here
    sets the admin's content language (`clanguage`), which the rest of the panel shares.

    @param string $clanguage
--}}
<x-filament::dropdown placement="bottom-end">
    <x-slot name="trigger">
        <x-filament::button color="gray">
            {{ strtoupper($clanguage) }}
        </x-filament::button>
    </x-slot>

    <x-filament::dropdown.list>
        @foreach (\Lara\Common\Models\Language::pluck('code') as $code)
            <x-filament::dropdown.list.item
                tag="a"
                :href="route('filament.admin.custom-blog.index', ['clanguage' => $code])"
                :color="$code === $clanguage ? 'primary' : 'gray'">
                {{ strtoupper($code) }}
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
