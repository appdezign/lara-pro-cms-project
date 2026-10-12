<!-- Test-Non-Livewire -->
<x-filament-panels::layout>
    <div class="fi-page">
        <div class="fi-page-header-main-ctn">
            <x-lara-legacy::page-header heading="Edit blog">
                <x-slot name="actions">
                    @include('lara-legacy::custom-blog._form-actions', ['formId' => 'lara-default-edit-form'])
                </x-slot>
            </x-lara-legacy::page-header>

            <div class="fi-page-main">
                <div class="fi-page-content">
                    <form
                        method="POST"
                        action="{{ route('filament.admin.custom-blog.update', $data->object) }}"
                        id="lara-default-edit-form"
                        class="fi-sc fi-grid fi-sc-has-gap"
                        style="--cols-default: repeat(1, minmax(0, 1fr))"
                        novalidate>
                        @csrf
                        @method('PATCH')

                        @include('lara-legacy::custom-blog._form')
                    </form>

                    @vite('laracms/app/Legacy/Resources/js/tiptap-editor.js', 'assets/admin/build')
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::layout>
