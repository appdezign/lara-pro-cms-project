<!-- Test-Non-Livewire -->
<x-filament-panels::layout>
    <div class="fi-page">
        <div class="fi-page-header-main-ctn">
            <x-lara-legacy::page-header :heading="$data->object->title">
                <x-slot name="actions">
                    <x-filament::button
                        tag="a"
                        color="gray"
                        :href="route('filament.admin.custom-blog.index')">
                        Back
                    </x-filament::button>
                </x-slot>
            </x-lara-legacy::page-header>

            <div class="fi-page-main">
                <div class="fi-page-content">
                    <x-filament::section heading="Body">
                        <div class="fi-prose">
                            {!! $data->object->body !!}
                        </div>
                    </x-filament::section>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::layout>
