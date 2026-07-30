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
		<table class="table w-full" >

			<thead>
				<tr>
					<th class="text-start" style="width:80%">Title</th>
					<th class="text-start" style="width:10%">Show</th>
					<th class="text-start" style="width:10%">Edit</th>
				</tr>
			</thead>
			<tbody>
				@foreach($data->objects as $object)
					<tr>
						<td>
							{{ $object->title }}
						</td>
						<td>
							<a href="{{ route('admin.custom-blog.show', ['id' => $object->id]) }}">
								show
							</a>
						</td>
						<td>
							<a href="{{ route('admin.custom-blog.edit', ['id' => $object->id]) }}">
								edit
							</a>
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</x-filament-panels::layout>