{{-- Form fields, shared by the create and edit views --}}
<x-filament::section heading="Content">
    <div class="fi-sc fi-grid fi-sc-has-gap" style="--cols-default: repeat(1, minmax(0, 1fr))">

        <x-filament-forms::field-wrapper
            id="title"
            state-path="title"
            label="Titel"
            :has-inline-label="true"
            :required="true">
            <x-filament::input.wrapper :valid="! $errors->has('title')">
                <x-filament::input
                    type="text"
                    id="title"
                    name="title"
                    required
                    :value="old('title', $data->object->title)"/>
            </x-filament::input.wrapper>
        </x-filament-forms::field-wrapper>

        <x-filament-forms::field-wrapper
            id="publish"
            state-path="publish"
            label="Publish"
            :has-inline-label="true"
            :required="true">
            <x-filament::input.wrapper :valid="! $errors->has('publish')">
                <x-filament::input.select id="publish" name="publish">
                    <option value="1" @selected(old('publish', $data->object->publish) == 1)>Yes</option>
                    <option value="0" @selected(old('publish', $data->object->publish) == 0)>No</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-filament-forms::field-wrapper>

    </div>
</x-filament::section>
