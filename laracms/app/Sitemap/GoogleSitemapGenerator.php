<?php

namespace Lara\App\Sitemap;

use Lara\Common\Models\Entity;
use Lara\Common\Models\Menu;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Page;

class GoogleSitemapGenerator
{

	/**
	 * Build the Google sitemap and write it to public/sitemap.xml.
	 *
	 * @param array<int, string> $locales Locales to include. Falls back to
	 *                                   laravellocalization.localesOrder when empty.
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

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . $eol;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . $eol;

		foreach ($languages as $language) {
			$mainMenu = Menu::where('slug', 'main')->first();
			if ($mainMenu) {
				$mainMenuId = $mainMenu->id;

				$menuItems = MenuItem::where('menu_id', $mainMenuId)->where('language', $language)->whereNull('tag_id')->get();

				foreach ($menuItems as $menuItem) {

					if ($menuItem->type == 'parent') {
						// folders
						$lastModified = $menuItem->updated_at;
					} elseif ($menuItem->type == 'page' || $menuItem->type == 'root') {
						// page
						$page = Page::where('id', $menuItem->object_id)->first();
						if ($page) {
							$lastModified = $page->updated_at;
						} else {
							$lastModified = $menuItem->updated_at;
						}
					} elseif ($menuItem->type == 'entity') {
						// modules
						$entity = Entity::where('id', $menuItem->entity_id)->first();
						$entityModel = $entity->entity_model_class;
						$lastObject = $entityModel::where('publish', 1)->orderBy('updated_at', 'desc')->first();
						if ($lastObject) {
							$lastModified = $lastObject->updated_at;
						} else {
							$lastModified = $menuItem->updated_at;
						}
						$entityMenuRoute = $menuItem->route;
					} elseif ($menuItem->type == 'form') {
						$lastModified = $menuItem->updated_at;
					} else {
						$lastModified = $menuItem->updated_at;
					}

					$lastModified = substr($lastModified, 0, 10);

					$objectRoute = htmlspecialchars($baseUrl . '/' . $language . '/' . $menuItem->route);

					$xml .= "\t" . '<url>' . $eol;
					$xml .= "\t\t" . '<loc>' . $objectRoute . '</loc>' . $eol;
					$xml .= "\t\t" . '<lastmod>' . $lastModified . '</lastmod>' . $eol;
					$xml .= "\t" . '</url>' . $eol;

					if ($menuItem->type == 'entity') {
						// modules
						$entity = Entity::where('id', $menuItem->entity_id)->first();
						$entityModel = $entity->entity_model_class;

						$entityHasTags = $entity->objectrelations->has_tags == 1;

						foreach ($entity->views as $view) {
							if ($view->method == 'show') {
								if ($entity->columns->has_status) {
									if ($entity->columns->has_expiration) {
										$entityObjects = $entityModel::langIs($language)->isPublished()->IsNotExpired()->get();
									} else {
										$entityObjects = $entityModel::langIs($language)->isPublished()->get();
									}
								} else {
									$entityObjects = $entityModel::langIs($language)->get();
								}

								foreach ($entityObjects as $object) {

									$singleUrl = $baseUrl . '/' . $language . '/' . $entityMenuRoute . '/' . $object->slug;

									if ($entityHasTags) {
										$objectRoute = htmlspecialchars($singleUrl . '.html');
									} else {
										$objectRoute = htmlspecialchars($singleUrl);
									}

									$lastModified = substr($object->updated_at, 0, 10);

									$xml .= "\t" . '<url>' . $eol;
									$xml .= "\t\t" . '<loc>' . $objectRoute . '</loc>' . $eol;
									$xml .= "\t\t" . '<lastmod>' . $lastModified . '</lastmod>' . $eol;
									$xml .= "\t" . '</url>' . $eol;
								}
							}
						}
					}
				}
			}
		}

		$xml .= '</urlset>' . $eol;

		$xml = utf8_encode($xml);

		$sitemap = public_path('sitemap.xml');

		$myfile = fopen($sitemap, "w") or die("Unable to open file!");
		fwrite($myfile, $xml);
		fclose($myfile);

	}

}
