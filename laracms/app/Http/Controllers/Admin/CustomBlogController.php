<?php

namespace Lara\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Lara\App\Models\Blog;
use stdClass;

/**
 * Example of a legacy (non-Livewire) admin controller inside the Filament panel.
 *
 * Its routes are registered in HasCustomNavigation::getCustomRoutes().
 */
class CustomBlogController extends Controller
{
    /**
     * The content language this example works in.
     */
    private const LANGUAGE = 'nl';

    protected stdClass $data;

    public function __construct()
    {
        $this->data = new stdClass;
    }

    public function index(): View
    {
        Gate::authorize('viewAny', Blog::class);

        $this->data->objects = Blog::where('language', self::LANGUAGE)->orderBy('title')->get();

        return view('lara-app::pages.custom-blog.index', [
            'data' => $this->data,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Blog::class);

        $this->data->object = new Blog;

        return view('lara-app::pages.custom-blog.create', [
            'data' => $this->data,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Blog::class);

        $blog = Blog::create([
            ...$this->validateBlog($request),
            'language' => self::LANGUAGE,
            'user_id' => $request->user()->id,
            'publish_from' => now(),
        ]);

        // refresh route cache, like LaraCreateRecord::afterCreate()
        session(['laracacheclear' => ['response_cache', 'route_cache']]);

        Notification::make()
            ->title(__('filament-panels::resources/pages/create-record.notifications.created.title'))
            ->success()
            ->send();

        return redirect()->route('filament.admin.custom-blog.edit', $blog);
    }

    public function show(Blog $blog): View
    {
        Gate::authorize('view', $blog);

        $this->data->object = $blog;

        return view('lara-app::pages.custom-blog.show', [
            'data' => $this->data,
        ]);
    }

    public function edit(Blog $blog): View
    {
        Gate::authorize('update', $blog);

        $this->data->object = $blog;

        return view('lara-app::pages.custom-blog.edit', [
            'data' => $this->data,
        ]);
    }

    public function update(Request $request, Blog $blog): RedirectResponse
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
     * @return array{title: string, publish: bool}
     */
    private function validateBlog(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'publish' => ['required', 'boolean'],
        ]);
    }
}
