# Inventory: seerdencommunicatie.nl (v8.2.46)

Generated with `php artisan lara:v8:inventory --markdown` on 2026-11-18, source `v8_seerden` (production dump of 2026-11-18), target `seerden10723` on v10.7.23 (demo install), `LARA_V8_SKIP_ENTITIES=city,product,sponsor,story`, media from the local v8 copy.


### Content types

| Key | Title | Group | v8 model | Table | Rows | Per language | Trashed | Fields | Relations |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| page | Pages | page | Lara\Common\Models\Page | lara_content_pages | 36 | en 2, nl 34 | 7 | 4 | 0 |
| blog | News | entity | Eve\Models\Blog | lara_content_blogs | 3 | nl 3 | 3 | 3 | 0 |
| team | Team | entity | Eve\Models\Team | lara_content_teams | 8 | nl 8 | 0 | 7 | 1 |
| event | Events | entity | Eve\Models\Event | lara_content_events | 5 | en 2, nl 3 | 0 | 4 | 1 |
| location | Locations | entity | Eve\Models\Location | lara_content_locations | 4 | nl 4 | 0 | 15 | 2 |
| slider | Sliders | block | Lara\Common\Models\Slider | lara_blocks_sliders | 6 | nl 6 | 4 | 12 | 0 |
| larawidget | Widgets | block | Lara\Common\Models\Larawidget | lara_blocks_larawidgets | 7 | nl 7 | 0 | 15 | 0 |
| portfolio | Portfolio | entity | Eve\Models\Portfolio | lara_content_portfolios | 27 | nl 27 | 8 | 1 | 0 |
| cta | Call-to-action | block | Lara\Common\Models\Cta | lara_blocks_ctas | 6 | en 2, nl 4 | 2 | 6 | 0 |
| contactform | Contactform | form | Eve\Models\Contactform | lara_form_contactforms | 5 | — | 0 | 4 | 0 |
| gallery | Galleries | entity | Eve\Models\Gallery | lara_content_galleries | 4 | nl 4 | 2 | 0 | 0 |
| video | Videos | entity | Eve\Models\Video | lara_content_videos | 13 | nl 13 | 3 | 0 | 0 |
| doc | Docs | entity | Eve\Models\Doc | lara_content_docs | 9 | nl 9 | 8 | 0 | 0 |
| classicform | Classicforms | form | Eve\Models\Classicform | lara_form_classicforms | 18 | — | 0 | 8 | 0 |
| testimonial | Testimonials | entity | Eve\Models\Testimonial | lara_content_testimonials | 4 | nl 4 | 0 | 3 | 0 |
| service | Services | entity | Eve\Models\Service | lara_content_services | 3 | nl 3 | 0 | 0 | 0 |
| project | Projects | entity | Eve\Models\Project | lara_content_projects | 7 | nl 7 | 0 | 5 | 0 |

### Skipped content types (v8-migration.skip_entities; not built, data not imported)

| Key | Title | Records | Media rows | Tags | Taggables | SEO rows | Related rows | Menu items |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| product | Products | 0 | 0 | 15 | 0 | 0 | 0 | 0 |
| sponsor | Sponsors | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| story | Stories | 1 | 0 | 3 | 1 | 0 | 0 | 0 |
| city | Cities | 1 | 2 | 9 | 0 | 1 | 1 | 0 |

### System screens (not entities in v10; their data is imported by the data commands)

| Key | Title | Group | v8 model |
| --- | --- | --- | --- |
| menuitem | Menu | menu | Lara\Common\Models\Menuitem |
| menu | Menus | menu | Lara\Common\Models\Menu |
| translation | Translations | tools | Lara\Common\Models\Translation |
| setting | Settings | tools | Lara\Common\Models\Setting |
| user | Users | auth | Lara\Common\Models\User |
| seo | Seos | seo | Lara\Common\Models\Seo |
| redirect | Redirects | seo | Lara\Common\Models\Redirect |
| role | Roles | auth | Lara\Common\Models\Userrole |
| ability | Abilities | auth | Lara\Common\Models\Userability |
| entitygroup | Entitygroups | builder | Lara\Common\Models\Entitygroup |
| tag | Tags | tag | Lara\Common\Models\Tag |
| language | Languages | menu | Lara\Common\Models\Language |
| headertag | Headertags | seo | Lara\Common\Models\Headertag |

### Custom field types (core content types keep their v10 fields)

| v8 type | Fields | v10 type | Used by |
| --- | --- | --- | --- |
| string | 34 | string | blog, classicform, contactform, cta, larawidget, location, page, portfolio, project, slider, team, testimonial |
| selectone | 14 | select | classicform, cta, larawidget, page, slider |
| boolean | 6 | toggle | blog, classicform, larawidget, page |
| text | 5 | textarea | classicform, contactform, project, slider, testimonial |
| decimal108 | 5 | latitude_10_8 | location |
| decimal118 | 5 | longitude_11_8 | location |
| date | 3 | date | classicform, event |
| custom | 3 | core only | larawidget |
| email | 2 | email | classicform, contactform |
| time | 2 | time | event |
| intunsigned | 2 | core only | larawidget |
| radio | 1 | radio | classicform |
| yesno | 1 | toggle | classicform |
| selectone2one | 1 | core only | cta |
| geolocation | 1 | geolocation | location |
| integer | 1 | number | project |
| mcemin | 1 | core only | slider |

### Custom field hooks and states (site content types)

| Kind | v8 value | Fields | v10 value |
| --- | --- | --- | --- |
| hook | after | 23 | after-last |
| hook | between | 13 | after-slug |
| hook | default | 12 | default |
| hook | before | 2 | before-title |
| state | enabled | 39 | enabled |
| state | enabledif | 10 | enabledif |
| state | hidden | 1 | hidden |

### Relations

| Entity | Type | Related | Foreign key | Filter | Same type in v10 |
| --- | --- | --- | --- | --- | --- |
| event | belongsTo | location | location_id | yes | yes |
| location | hasMany | event | location_id | no | yes |
| ? | hasMany | ? | city_id | no | yes |
| ? | belongsTo | ? | city_id | no | yes |
| location | hasMany | team | location_id | no | yes |
| team | belongsTo | location | location_id | no | yes |

### Object relations and media settings

| Entity | Has | Related | Tags | Media (max) | Disks |
| --- | --- | --- | --- | --- | --- |
| page | seo opengraph layout sync | related relatable |  | images 24, videos 1, videofiles 1, files 24 | localdisk |
| blog | seo opengraph | related relatable | yes | images 24, videos 1, videofiles 1, files 24 | localdisk |
| team | seo opengraph | related relatable | yes | images 1, videos 1, files 1 | localdisk |
| event |  |  | yes | images 24 | localdisk |
| location |  |  |  |  | localdisk |
| slider |  |  | yes (default: home) | images 1 | localdisk |
| larawidget |  |  |  | images 1 | localdisk |
| portfolio |  |  |  | images 1 | localdisk |
| cta |  |  |  | images 1 | localdisk |
| contactform |  |  |  |  | localdisk |
| gallery |  |  | yes | images 24 | localdisk |
| video | seo |  | yes | videos 1 | localdisk |
| doc |  | relatable | yes | files 1 |  |
| classicform |  |  |  |  | localdisk |
| testimonial |  |  | yes | images 1 | localdisk |
| service |  |  | yes | images 1 | localdisk |
| project | seo | related relatable | yes | images 24 | localdisk |

### Core field values (v8 data, translated, against the v10 options of the target site)

| Entity | v10 field | v8 column | Live records | Other records | Added to options by import-entities |
| --- | --- | --- | --- | --- | --- |
| larawidget | hook | hook | content_bottom (5), footer1 (1) | content_bottom (1) | — |
| larawidget | iconalign | iconalign | — | — | — |
| larawidget | resource_slug | relentkey | projects (5) | projects (1) | dynamic (get_entity_resources): check the theme |
| larawidget | resource_term | term | campagnes (1), event-aanspreekpunt (1), event-ontzorgen (1), event-organisatie-en-management (1), event-sportevenementen (1) | event-coordinatie (1) | dynamic (get_entity_terms): check the theme |
| larawidget | template | template | campagne (1), event (4), footer (1) | event (1) | dynamic (get_widget_templates): check the theme |
| larawidget | type | type | module (5), text (1) | module (1) | — |
| cta | hook | hook | contact (1), newsletter (1) | homeparalax1 (1), homeparalax2 (1), homeparallax1 (1), homeparallax2 (1) | — |
| cta | overlaycolor | overlaycolor | black (2) | black (2), brand (2) | — |
| cta | overlaytransp | overlaytransp | — | 40 (2), 60 (2) | — |
| cta | type | type | solid (2) | parallax (4) | — |
| slider | captiontype | captiontype | — | block (3) | — |
| slider | overlaycolor | overlaycolor | — | black (4) | — |
| slider | overlaysize | overlaysize | — | full (4) | — |
| slider | overlaytransp | overlaytransp | — | 10 (1), 40 (2) | — |
| slider | textposition | textposition | — | center (4) | — |
| slider | type | type | image (1) | image (2), payoff (3) | — |

### Front views

| Entity | Method | File | Type | Show tags | Paginate | Published |
| --- | --- | --- | --- | --- | --- | --- |
| page | show | show | _single | none | 0 | yes |
| blog | index | index | list | none | 6 | yes |
| blog | show | show | _single | none | 0 | yes |
| team | index | index | grid4 | none | 0 | yes |
| team | show | show | _single | none | 0 | yes |
| event | index | index | list | none | 0 | yes |
| event | show | show | _single | none | 0 | yes |
| location | index | index | list | none | 0 | yes |
| location | show | show | _single | none | 0 | yes |
| contactform | form | form | _form | none | 0 | yes |
| ? | index | index | list | none | 0 | no |
| ? | show | show | _single | none | 0 | no |
| gallery | index | index | grid3 | none | 12 | yes |
| gallery | show | show | _single | none | 0 | yes |
| video | index | index | list | none | 12 | yes |
| video | show | show | _single | none | 0 | yes |
| doc | index | index | list | _sortbytaxonomy | 0 | yes |
| doc | show | show | _single | none | 0 | yes |
| classicform | form | form | _form | none | 0 | yes |
| ? | index | index | list | none | 10 | yes |
| ? | show | show | _single | none | 0 | yes |
| testimonial | index | index | list | none | 0 | yes |
| testimonial | show | show | _single | none | 0 | yes |
| ? | index | index | grid3 | none | 6 | yes |
| ? | show | show | _single | none | 0 | yes |
| service | index | index | list | filterbytaxonomy | 0 | yes |
| service | show | show | _single | none | 0 | yes |
| project | index | index | grid2 | none | 0 | yes |
| project | show | show | _single | none | 0 | yes |

### Media rows per model

| v8 model | Entity | Images | Files | Videos | Videofiles |
| --- | --- | --- | --- | --- | --- |
| Eve\Models\City | city | 1 | 1 | 0 | 0 |
| Eve\Models\Doc | doc | 0 | 1 | 0 | 0 |
| Eve\Models\Event | event | 3 | 0 | 0 | 0 |
| Eve\Models\Gallery | gallery | 10 | 0 | 0 | 0 |
| Eve\Models\Portfolio | portfolio | 18 | 0 | 0 | 0 |
| Eve\Models\Project | project | 22 | 0 | 0 | 0 |
| Eve\Models\Service | service | 3 | 0 | 0 | 0 |
| Eve\Models\Team | team | 8 | 1 | 1 | 0 |
| Eve\Models\Video | video | 0 | 0 | 10 | 0 |
| Lara\Common\Models\Page | page | 2 | 0 | 0 | 0 |
| Lara\Common\Models\Slider | slider | 2 | 0 | 0 | 0 |
| Lara\Common\Models\Tag | tag | 1 | 0 | 0 | 0 |

### Media files on disk (/Users/hoeksms/Code/__cloudways/__migrate/seerden8246/public/assets/media)

| Folder | Content type | Rows | Only in _archive | Missing | Files in folder | Not referenced | Subfolders |
| --- | --- | --- | --- | --- | --- | --- | --- |
| _temp | — | 0 | 0 | 0 | 4 | 4 |  |
| blog | yes | 0 | 0 | 0 | 0 | 0 |  |
| city | — | 0 | 0 | 0 | 0 | 0 |  |
| contactform | yes | 0 | 0 | 0 | 0 | 0 |  |
| cta | yes | 0 | 0 | 0 | 0 | 0 |  |
| default | — | 0 | 0 | 0 | 1 | 1 |  |
| doc | yes | 1 | 0 | 0 | 1 | 0 |  |
| event | yes | 3 | 0 | 1 | 2 | 0 |  |
| gallery | yes | 10 | 0 | 0 | 19 | 9 |  |
| images | — | 0 | 0 | 0 | 0 | 0 | shares |
| larawidget | yes | 0 | 0 | 0 | 5 | 5 |  |
| location | yes | 0 | 0 | 0 | 0 | 0 |  |
| page | yes | 2 | 0 | 0 | 2 | 0 |  |
| portfolio | yes | 18 | 0 | 0 | 26 | 8 |  |
| product | — | 0 | 0 | 0 | 0 | 0 |  |
| project | yes | 22 | 0 | 0 | 22 | 0 |  |
| service | yes | 3 | 0 | 0 | 3 | 0 |  |
| slider | yes | 2 | 0 | 0 | 8 | 6 |  |
| sponsor | — | 0 | 0 | 0 | 0 | 0 |  |
| story | — | 0 | 0 | 0 | 0 | 0 |  |
| tag | — | 1 | 0 | 0 | 3 | 2 |  |
| team | yes | 9 | 0 | 1 | 8 | 0 |  |
| testimonial | yes | 0 | 0 | 0 | 6 | 6 |  |
| video | yes | 0 | 0 | 0 | 0 | 0 |  |

### Other data

| What | Rows | Detail |
| --- | --- | --- |
| Users | 7 |  |
| Roles | 4 | member, editor, webmaster, administrator |
| Abilities | 124 |  |
| Languages | 2 | nl, en |
| Menus | 3 |  |
| Menu items | 24 | entity 5, form 2, page 12, parent 1, root 4 |
| Redirects | 0 |  |
| Taxonomies | 2 | category, tag |
| Tags | 143 | blog 18, city 9, cta 1, doc 7, event 10, gallery 7, larawidget 1, location 1, page 1, portfolio 13, product 15, project 9, service 9, setting 1, slider 10, story 3, team 21, testimonial 4, video 3 |
| Taggables | 26 |  |
| SEO rows | 49 |  |
| Opengraph rows | 20 |  |
| Layout rows | 3 |  |
| Related rows | 28 |  |
| Pageables | 6 |  |
| Sync rows | 0 |  |
| Settings | 41 | company 21, general 2, opengraph 5, system 13 |
| Translations | 1743 | lara-admin 1238, lara-common 19, lara-eve 353, lara-front 133 |
| Header tags | 21 |  |
| Blacklist | 0 |  |

### Findings (no v10 mapping, a change in behaviour, or inconsistent v8 data)

| # | Finding |
| --- | --- |
| 1 | Content type 'story' is skipped: 1 record(s), 0 media row(s) and 0 menu item(s) are not imported. |
| 2 | Content type 'city' is skipped: 1 record(s), 2 media row(s) and 0 menu item(s) are not imported. |
| 3 | Field type 'yesno': the yes/no choice becomes a toggle (classicform.myyesno). |
| 4 | cta.hook: 'homeparalax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 5 | cta.hook: 'homeparalax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 6 | cta.hook: 'homeparallax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 7 | cta.hook: 'homeparallax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 8 | 1 media row(s) of 'event' point to a file that is missing (also not in _archive): /Users/hoeksms/Code/__cloudways/__migrate/seerden8246/public/assets/media/event. |
| 9 | 1 media row(s) of 'team' point to a file that is missing (also not in _archive): /Users/hoeksms/Code/__cloudways/__migrate/seerden8246/public/assets/media/team. |
| 10 | Custom field location.latitude is defined 5 times. |
| 11 | Custom field location.longitude is defined 5 times. |

11 finding(s): see the last section.
