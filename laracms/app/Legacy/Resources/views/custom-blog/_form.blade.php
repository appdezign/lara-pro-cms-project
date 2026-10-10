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

        {{--
            Rich text editor (Tiptap, laracms/app/Legacy/Resources/js/tiptap-editor.js).
            The hidden textarea stays the form field: the editor writes its HTML into it.
        --}}
        <x-filament-forms::field-wrapper
            id="body"
            state-path="body"
            label="Body"
            :has-inline-label="true">
            <x-filament::input.wrapper class="fi-fo-rich-editor" :valid="! $errors->has('body')">
                <div x-data="laraTiptapEditor()">
                    <div class="fi-fo-rich-editor-toolbar" aria-label="Body formatting">
                        <div class="fi-fo-rich-editor-toolbar-group">
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Bold"
                                title="Bold"
                                x-bind:class="{ 'fi-active': isActive('bold') }"
                                x-on:click="run('toggleBold')">
                                <x-filament::icon icon="heroicon-m-bold"/>
                            </button>
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Italic"
                                title="Italic"
                                x-bind:class="{ 'fi-active': isActive('italic') }"
                                x-on:click="run('toggleItalic')">
                                <x-filament::icon icon="heroicon-m-italic"/>
                            </button>
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Underline"
                                title="Underline"
                                x-bind:class="{ 'fi-active': isActive('underline') }"
                                x-on:click="run('toggleUnderline')">
                                <x-filament::icon icon="heroicon-m-underline"/>
                            </button>
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Strikethrough"
                                title="Strikethrough"
                                x-bind:class="{ 'fi-active': isActive('strike') }"
                                x-on:click="run('toggleStrike')">
                                <x-filament::icon icon="heroicon-m-strikethrough"/>
                            </button>
                        </div>
                        <div class="fi-fo-rich-editor-toolbar-group">
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Heading 2"
                                title="Heading 2"
                                x-bind:class="{ 'fi-active': isActive('heading', { level: 2 }) }"
                                x-on:click="run('toggleHeading', { level: 2 })">
                                <x-filament::icon icon="heroicon-m-h2"/>
                            </button>
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Heading 3"
                                title="Heading 3"
                                x-bind:class="{ 'fi-active': isActive('heading', { level: 3 }) }"
                                x-on:click="run('toggleHeading', { level: 3 })">
                                <x-filament::icon icon="heroicon-m-h3"/>
                            </button>
                        </div>
                        <div class="fi-fo-rich-editor-toolbar-group">
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Bullet list"
                                title="Bullet list"
                                x-bind:class="{ 'fi-active': isActive('bulletList') }"
                                x-on:click="run('toggleBulletList')">
                                <x-filament::icon icon="heroicon-m-list-bullet"/>
                            </button>
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Numbered list"
                                title="Numbered list"
                                x-bind:class="{ 'fi-active': isActive('orderedList') }"
                                x-on:click="run('toggleOrderedList')">
                                <x-filament::icon icon="heroicon-m-numbered-list"/>
                            </button>
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Blockquote"
                                title="Blockquote"
                                x-bind:class="{ 'fi-active': isActive('blockquote') }"
                                x-on:click="run('toggleBlockquote')">
                                <x-filament::icon icon="heroicon-m-chat-bubble-bottom-center-text"/>
                            </button>
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Link"
                                title="Link"
                                x-bind:class="{ 'fi-active': isActive('link') }"
                                x-on:click="setLink()">
                                <x-filament::icon icon="heroicon-m-link"/>
                            </button>
                        </div>
                        <div class="fi-fo-rich-editor-toolbar-group">
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Undo"
                                title="Undo"
                                x-on:click="run('undo')">
                                <x-filament::icon icon="heroicon-m-arrow-uturn-left"/>
                            </button>
                            <button
                                type="button"
                                class="fi-fo-rich-editor-tool"
                                aria-label="Redo"
                                title="Redo"
                                x-on:click="run('redo')">
                                <x-filament::icon icon="heroicon-m-arrow-uturn-right"/>
                            </button>
                        </div>
                    </div>

                    <div class="fi-fo-rich-editor-main">
                        <div class="fi-fo-rich-editor-content fi-prose" x-ref="editor"></div>
                    </div>

                    <textarea
                        id="body"
                        name="body"
                        hidden
                        x-ref="textarea">{{ old('body', $data->object->body) }}</textarea>
                </div>
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
