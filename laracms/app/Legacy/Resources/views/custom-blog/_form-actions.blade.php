{{--
    Header buttons for the create and edit views.
    They sit outside the <form>, so Save submits it through the form attribute.

    @param string $formId
--}}
<x-filament::button
    tag="a"
    color="gray"
    :href="route('filament.admin.custom-blog.index')">
    Cancel
</x-filament::button>
<x-filament::button type="submit" :form-id="$formId" id="globalsave">
    Save
</x-filament::button>
