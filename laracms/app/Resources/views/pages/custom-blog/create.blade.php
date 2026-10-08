<!-- Test-Non-Livewire -->
<x-filament-panels::layout>
    <div class="fi-page">
        <div class="fi-page-header-main-ctn">
            <x-lara-app::admin.page-header heading="New blog">
                <x-slot name="actions">
                    @include('lara-app::pages.custom-blog._form-actions', ['formId' => 'lara-default-create-form'])
                </x-slot>
            </x-lara-app::admin.page-header>

            <div class="fi-page-main">
                <div class="fi-page-content">
                    <form
                        method="POST"
                        action="{{ route('filament.admin.custom-blog.store') }}"
                        id="lara-default-create-form"
                        class="fi-sc fi-grid fi-sc-has-gap"
                        style="--cols-default: repeat(1, minmax(0, 1fr))"
                        novalidate>
                        @csrf

                        @include('lara-app::pages.custom-blog._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::layout>
