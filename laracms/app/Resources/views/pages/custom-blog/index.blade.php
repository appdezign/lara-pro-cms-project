<!-- Test-Non-Livewire -->
<x-filament-panels::layout>
    <div class="fi-page">
        <div class="fi-page-header-main-ctn">
            <x-lara-app::admin.page-header heading="Custom Blogs">
                <x-slot name="actions">
                    @can('create', \Lara\App\Models\Blog::class)
                        <x-filament::button
                            tag="a"
                            icon="heroicon-m-plus"
                            :href="route('filament.admin.custom-blog.create')">
                            New blog
                        </x-filament::button>
                    @endcan
                </x-slot>
            </x-lara-app::admin.page-header>

            <div class="fi-page-main">
                <div class="fi-page-content">
                    <div class="fi-ta">
                        <div class="fi-ta-ctn">
                            <div class="fi-ta-main">
                                <div class="fi-ta-content-ctn">
                                    <table class="fi-ta-table">
                                        <thead>
                                            <tr>
                                                <th class="fi-ta-header-cell">Published</th>
                                                <th class="fi-ta-header-cell">Title</th>
                                                <th class="fi-ta-header-cell fi-align-end"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($data->objects as $object)
                                                <tr class="fi-ta-row">
                                                    <td class="fi-ta-cell">
                                                        <div class="fi-ta-col">
                                                            <div class="fi-ta-text fi-ta-text-item">
                                                                @if($object->publish)
                                                                    <x-filament::badge color="success">Yes</x-filament::badge>
                                                                @else
                                                                    <x-filament::badge color="gray">No</x-filament::badge>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="fi-ta-cell">
                                                        <div class="fi-ta-col">
                                                            <div class="fi-ta-text fi-ta-text-item fi-size-sm">
                                                                {{ $object->title }}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="fi-ta-cell">
                                                        <div class="fi-ta-actions fi-align-end">
                                                            <x-filament::link
                                                                :href="route('filament.admin.custom-blog.show', $object)"
                                                                color="gray"
                                                                icon="heroicon-m-eye"
                                                                size="sm">
                                                                Show
                                                            </x-filament::link>
                                                            <x-filament::link
                                                                :href="route('filament.admin.custom-blog.edit', $object)"
                                                                icon="heroicon-m-pencil-square"
                                                                size="sm">
                                                                Edit
                                                            </x-filament::link>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3">
                                                        <x-filament::empty-state heading="No blogs found" icon="heroicon-o-x-mark"/>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::layout>
