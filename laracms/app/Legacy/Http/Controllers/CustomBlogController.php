<?php

namespace Lara\App\Legacy\Http\Controllers;

use App\Http\Controllers\Controller;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Lara\Admin\Concerns\HasLanguage;
use Lara\Admin\Concerns\HasParams;
use Lara\App\Legacy\Models\CustomBlog;
use stdClass;

/**
 * Example of a legacy (non-Livewire) admin controller inside the Filament panel.
 *
 * It works on its own model and table (CustomBlog, lara_custom_blogs), separate from the entities,
 * in the admin's content language (`clanguage`), which it shares with the rest of the panel.
 * Its routes are registered in LegacyNavigation::routes().
 */
class CustomBlogController extends Controller
{
    use HasLanguage;
    use HasParams;

    protected stdClass $data;

    public function __construct()
    {
        $this->data = new stdClass;
    }

    public function index(): View
    {
        Gate::authorize('viewAny', CustomBlog::class);

        $this->data->clanguage = static::getContentLanguage();
        $this->data->objects = CustomBlog::where('language', $this->data->clanguage)->orderBy('title')->get();

        return view('lara-legacy::custom-blog.index', [
            'data' => $this->data,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', CustomBlog::class);

        $this->data->object = new CustomBlog;

        return view('lara-legacy::custom-blog.create', [
            'data' => $this->data,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', CustomBlog::class);

        $blog = CustomBlog::create([
            ...$this->validateBlog($request),
            'language' => static::getContentLanguage(),
            'user_id' => $request->user()->id,
        ]);

        Notification::make()
            ->title(__('filament-panels::resources/pages/create-record.notifications.created.title'))
            ->success()
            ->send();

        return redirect()->route('filament.admin.custom-blog.edit', $blog);
    }

    public function show(CustomBlog $blog): View
    {
        Gate::authorize('view', $blog);

        $this->data->object = $blog;

        return view('lara-legacy::custom-blog.show', [
            'data' => $this->data,
        ]);
    }

    public function edit(CustomBlog $blog): View
    {
        Gate::authorize('update', $blog);

        $this->data->object = $blog;

        return view('lara-legacy::custom-blog.edit', [
            'data' => $this->data,
        ]);
    }

    public function update(Request $request, CustomBlog $blog): RedirectResponse
    {
        Gate::authorize('update', $blog);

        $blog->update($this->validateBlog($request));

        Notification::make()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->success()
            ->send();

        return redirect()->route('filament.admin.custom-blog.edit', $blog);
    }

    /**
     * The body is sanitized after validation (scripts, event handlers and javascript: links are removed),
     * and stored as null when nothing is left.
     *
     * @return array{title: string, publish: bool, body: ?string}
     */
    private function validateBlog(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'publish' => ['required', 'boolean'],
            'body' => ['nullable', 'string', 'max:65535'],
        ]);

        $body = filled($validated['body'] ?? null) ? Str::sanitizeHtml($validated['body']) : null;

        return [...$validated, 'body' => filled($body) ? $body : null];
    }
}
