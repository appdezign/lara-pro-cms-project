<?php

namespace Lara\App\Sitemap;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Lara\Admin\Enums\MenuItemType;
use Lara\Common\Models\Entity;
use Lara\Common\Models\Menu;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Page;

class GoogleSitemapGenerator
{
    /**
     * Build the Google sitemap and write it to public/sitemap.xml.
     *
     * @param  array<int, string>  $locales  Locales to include. Falls back to
     *                                       laravellocalization.localesOrder when empty.
     */
    public function __invoke(array $locales = []): void
    {

        $eol = PHP_EOL;

        $baseUrl = url('/');

        if (empty($locales)) {
            $languages = config('laravellocalization.localesOrder');
        } else {
            $languages = $locales;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.$eol;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$eol;

        foreach ($languages as $language) {
            $mainMenu = Menu::where('slug', 'main')->first();
            if ($mainMenu) {
                $mainMenuId = $mainMenu->id;

                $menuItems = MenuItem::where('menu_id', $mainMenuId)->where('language', $language)->whereNull('tag_id')->get();

                foreach ($menuItems as $menuItem) {

                    // MenuItem::type is cast to MenuItemType: compare with the enum, not with strings
                    $entity = $menuItem->type === MenuItemType::Entity ? Entity::find($menuItem->entity_id) : null;

                    // a page item whose page is gone (trashed) answers 404: not listed
                    $page = $menuItem->type === MenuItemType::Page ? Page::find($menuItem->object_id) : null;
                    if ($menuItem->type === MenuItemType::Page && $page === null) {
                        continue;
                    }

                    $lastModified = match ($menuItem->type) {
                        MenuItemType::Page => $page->updated_at ?? $menuItem->updated_at,
                        MenuItemType::Entity => $this->publishedObjects($entity, $language)->max('updated_at') ?? $menuItem->updated_at,
                        default => $menuItem->updated_at,
                    };

                    $xml .= $this->url($baseUrl.'/'.$language.'/'.$menuItem->route, $lastModified, $eol);

                    // the detail pages of a module (only when the entity has a show view)
                    if ($entity !== null && $entity->views()->where('method', 'show')->exists()) {
                        // with terms the detail route is {route}/{slug}.html (laracms/app/Routes/web.php)
                        $suffix = $entity->objrel_has_terms ? '.html' : '';

                        foreach ($this->publishedObjects($entity, $language) as $object) {
                            $xml .= $this->url($baseUrl.'/'.$language.'/'.$menuItem->route.'/'.$object->slug.$suffix, $object->updated_at, $eol);
                        }
                    }
                }
            }
        }

        $xml .= '</urlset>'.$eol;

        $sitemap = public_path('sitemap.xml');

        $myfile = fopen($sitemap, 'w') or exit('Unable to open file!');
        fwrite($myfile, $xml);
        fclose($myfile);

    }

    /**
     * One <url> entry. The values are UTF-8 already (no utf8_encode(): it is deprecated and
     * would encode them twice).
     */
    private function url(string $location, mixed $lastModified, string $eol): string
    {
        return "\t".'<url>'.$eol
            ."\t\t".'<loc>'.htmlspecialchars($location).'</loc>'.$eol
            ."\t\t".'<lastmod>'.substr((string) $lastModified, 0, 10).'</lastmod>'.$eol
            ."\t".'</url>'.$eol;
    }

    /**
     * The objects of a module the front shows: in the language, published and not expired when
     * the entity has a status and an expiration.
     *
     * @return Collection<int, Model>
     */
    private function publishedObjects(?Entity $entity, string $language): Collection
    {
        if ($entity === null || ! class_exists($entity->model_class)) {
            return new Collection;
        }

        return $entity->model_class::langIs($language)
            ->when($entity->col_has_status, fn ($query) => $query->isPublished())
            ->when($entity->col_has_status && $entity->col_has_expiration, fn ($query) => $query->isNotExpired())
            ->get();
    }
}
