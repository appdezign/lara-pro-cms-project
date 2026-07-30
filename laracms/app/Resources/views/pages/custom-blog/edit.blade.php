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

		{{ html()->modelForm($data->object,
			'PATCH',
			route('admin.custom-blog.update', ['id' => $data->object->id]))
			->id('lara-default-edit-form')
			->attributes(['accept-charset' => 'UTF-8'])
			->class('needs-validation')
			->novalidate()
			->open() }}

		<div class="fi-sc-component">
			<div data-field-wrapper="" class="fi-fo-field fi-fo-field-has-inline-label">

				<div class="fi-fo-field-label-col fi-vertical-align-center">
					<div class="fi-fo-field-label-ctn ">
						<label for="form.max_items" class="fi-fo-field-label">
							<span class="fi-fo-field-label-content">
                                {{ html()->label('Titel', 'title') }}
							</span>
						</label>
					</div>
				</div>

				<div class="fi-fo-field-content-col">
					<div class="fi-input-wrp fi-fo-text-input">
						<div class="fi-input-wrp-content-ctn">
							{{ html()->text('title', null)->class('fi-input') }}
						</div>
					</div>
				</div>

			</div>
		</div>

		{{ html()->button('save', 'submit')->class('fi-color fi-color-danger fi-bg-color-600 hover:fi-bg-color-500 fi-text-color-50 hover:fi-text-color-50 fi-btn fi-size-md  fi-ac-btn-action mt-4')->id('globalsave') }}

		{{ html()->closeModelForm() }}

	</div>
</x-filament-panels::layout>