<!-- Test-Non-Livewire -->
<x-filament-panels::layout>
	<div class="fi-page-header flex flex-col gap-4-2 sm:flex-row sm:items-center sm:justify-between mb-6">
		<div>
			<h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
				Custom Blogs
			</h1>
		</div>
	</div>

	<div class="bg-white p-6 rounded-xl shadow-sm dark:bg-gray-800 p-4">
		<h3>{{ $data->object->title }}</h3>

		{!! $data->object->lead !!}

		{!! $data->object->body !!}

	</div>
</x-filament-panels::layout>