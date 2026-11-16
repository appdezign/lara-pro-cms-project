# Inventory: anpak.eu (v8.2.46)

Generated with `php artisan lara:v8:inventory --markdown` on 2026-11-16, source `v8_anpak` (production dump of 2026-11-14), target `anpak1077` on v10.7.18.

### Content types

| Key | Title | Group | v8 model | Table | Rows | Per language | Trashed | Fields | Relations |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| page | Pages | page | Lara\Common\Models\Page | lara_content_pages | 46 | en 2, nl 44 | 1 | 3 | 0 |
| blog | News | entity | Eve\Models\Blog | lara_content_blogs | 21 | en 4, nl 17 | 0 | 3 | 0 |
| team | Team | entity | Eve\Models\Team | lara_content_teams | 12 | nl 12 | 4 | 7 | 0 |
| event | Events | entity | Eve\Models\Event | lara_content_events | 0 | 0 | 0 | 4 | 1 |
| location | Locations | entity | Eve\Models\Location | lara_content_locations | 0 | 0 | 0 | 9 | 1 |
| slider | Sliders | block | Lara\Common\Models\Slider | lara_blocks_sliders | 14 | nl 14 | 0 | 12 | 0 |
| larawidget | Widgets | block | Lara\Common\Models\Larawidget | lara_blocks_larawidgets | 16 | en 12, nl 4 | 0 | 15 | 0 |
| portfolio | Portfolio | entity | Eve\Models\Portfolio | lara_content_portfolios | 6 | nl 6 | 0 | 1 | 0 |
| cta | Call-to-action | block | Lara\Common\Models\Cta | lara_blocks_ctas | 6 | en 2, nl 4 | 3 | 6 | 0 |
| contactform | Contactform | form | Eve\Models\Contactform | lara_form_contactforms | 43 | — | 0 | 4 | 0 |
| gallery | Galleries | entity | Eve\Models\Gallery | lara_content_galleries | 1 | nl 1 | 0 | 0 | 0 |
| video | Videos | entity | Eve\Models\Video | lara_content_videos | 1 | nl 1 | 0 | 0 | 0 |
| doc | Docs | entity | Eve\Models\Doc | lara_content_docs | 27 | en 11, nl 16 | 3 | 0 | 0 |
| classicform | Classicforms | form | Eve\Models\Classicform | lara_form_classicforms | 20 | — | 0 | 8 | 0 |
| testimonial | Testimonials | entity | Eve\Models\Testimonial | lara_content_testimonials | 11 | nl 11 | 0 | 2 | 0 |
| service | Services | entity | Eve\Models\Service | lara_content_services | 18 | en 7, nl 11 | 0 | 1 | 0 |
| job | Jobs | entity | Eve\Models\Job | lara_content_jobs | 2 | nl 2 | 0 | 5 | 0 |
| application | Applications | form | Eve\Models\Application | lara_form_applications | 4 | — | 0 | 12 | 0 |
| client | Clients | entity | Eve\Models\Client | lara_content_clients | 29 | nl 29 | 1 | 0 | 0 |

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
| string | 41 | string | application, blog, classicform, contactform, cta, job, larawidget, location, page, portfolio, service, slider, team, testimonial |
| selectone | 14 | select | classicform, cta, larawidget, page, slider |
| date | 7 | date | application, classicform, event, job |
| boolean | 6 | toggle | blog, classicform, larawidget, page |
| email | 4 | email | application, classicform, contactform, job |
| text | 4 | textarea | application, classicform, contactform, slider |
| custom | 3 | core only | larawidget |
| time | 2 | time | event |
| intunsigned | 2 | core only | larawidget |
| decimal108 | 2 | latitude_10_8 | location |
| decimal118 | 2 | longitude_11_8 | location |
| radio | 1 | radio | classicform |
| yesno | 1 | toggle | classicform |
| selectone2one | 1 | core only | cta |
| geolocation | 1 | geolocation | location |
| mcemin | 1 | core only | slider |

### Custom field hooks and states (site content types)

| Kind | v8 value | Fields | v10 value |
| --- | --- | --- | --- |
| hook | default | 24 | default |
| hook | after | 24 | after-last |
| hook | between | 6 | after-slug |
| hook | before | 2 | before-title |
| state | enabled | 50 | enabled |
| state | enabledif | 4 | enabledif |
| state | hidden | 2 | hidden |

### Relations

| Entity | Type | Related | Foreign key | Filter | Same type in v10 |
| --- | --- | --- | --- | --- | --- |
| event | belongsTo | location | location_id | yes | yes |
| location | hasMany | event | location_id | no | yes |

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
| portfolio |  |  | yes | images 1 | localdisk |
| cta |  |  |  | images 1 | localdisk |
| contactform |  |  |  |  | localdisk |
| gallery |  |  | yes | images 24 | localdisk |
| video | seo |  | yes | videos 1 | localdisk |
| doc |  | relatable | yes | files 1 | localdisk |
| classicform |  |  |  |  | localdisk |
| testimonial |  |  | yes | images 1 | localdisk |
| service |  |  | yes | images 1 | localdisk |
| job | seo |  |  | files 1 | localdisk |
| application |  |  |  | files 1 | localdisk |
| client |  |  |  | images 1 | localdisk |

### Core field values (v8 data, translated, against the v10 options of the target site)

| Entity | v10 field | v8 column | Live records | Other records | Added to options by import-entities |
| --- | --- | --- | --- | --- | --- |
| larawidget | hook | hook | home1 (2), home2 (1), home3 (1) | footer1 (1), footer2 (1), home1 (1), home10 (1), home2 (1), home3 (1), home4 (1), home5 (1), home6 (1), home7 (1), home8 (1), home9 (1) | — |
| larawidget | iconalign | iconalign | — | left (6), top (3) | — |
| larawidget | resource_slug | relentkey | — | — | dynamic (get_entity_resources): check the theme |
| larawidget | resource_term | term | — | — | dynamic (get_entity_terms): check the theme |
| larawidget | template | template | service (4) | default (12) | dynamic (get_widget_templates): check the theme |
| larawidget | type | type | text (4) | icon (9), text (3) | — |
| cta | hook | hook | homeparallax1 (1) | homeparalax1 (1), homeparalax2 (1), homeparallax1 (1), homeparallax2 (1), newsletter (1) | — |
| cta | overlaycolor | overlaycolor | brand (1) | black (3), brand (2) | — |
| cta | overlaytransp | overlaytransp | 80 (1) | 40 (2), 60 (2) | — |
| cta | type | type | parallax (1) | parallax (4), solid (1) | — |
| slider | captiontype | captiontype | block (4), fade (1) | — | — |
| slider | overlaycolor | overlaycolor | black (8) | — | — |
| slider | overlaysize | overlaysize | full (8) | — | — |
| slider | overlaytransp | overlaytransp | 10 (1), 30 (1), 40 (4) | — | — |
| slider | textposition | textposition | center (8) | — | — |
| slider | type | type | caption (1), image (5), payoff (8) | — | — |

### Front views

| Entity | Method | File | Type | Show tags | Paginate | Published |
| --- | --- | --- | --- | --- | --- | --- |
| page | show | show | _single | none | 0 | yes |
| blog | index | index | list | none | 6 | yes |
| blog | show | show | _single | none | 0 | yes |
| team | index | index | grid3 | none | 0 | yes |
| team | show | show | _single | none | 0 | yes |
| event | index | index | list | none | 0 | yes |
| event | show | show | _single | none | 0 | yes |
| location | index | index | list | none | 0 | yes |
| location | show | show | _single | none | 0 | yes |
| contactform | form | form | _form | none | 0 | yes |
| gallery | index | index | grid3 | none | 12 | yes |
| gallery | show | show | _single | none | 0 | yes |
| video | index | index | list | none | 12 | yes |
| video | show | show | _single | none | 0 | yes |
| doc | index | index | list | _sortbytaxonomy | 0 | yes |
| doc | show | show | _single | none | 0 | yes |
| classicform | form | form | _form | none | 0 | yes |
| testimonial | index | index | list | none | 0 | yes |
| testimonial | show | show | _single | none | 0 | yes |
| service | index | index | list | _sortbytaxonomy | 0 | yes |
| service | show | show | _single | none | 0 | yes |
| job | index | index | list | none | 0 | yes |
| job | show | show | _single | none | 0 | yes |
| application | form | form | _form | none | 0 | yes |
| client | index | index | list | none | 0 | yes |
| client | show | show | _single | none | 0 | yes |

### Media rows per model

| v8 model | Entity | Images | Files | Videos | Videofiles |
| --- | --- | --- | --- | --- | --- |
| Eve\Models\Application | application | 0 | 5 | 0 | 0 |
| Eve\Models\Blog | blog | 13 | 0 | 0 | 0 |
| Eve\Models\Client | client | 27 | 0 | 0 | 0 |
| Eve\Models\Doc | doc | 0 | 12 | 0 | 0 |
| Eve\Models\Gallery | gallery | 1 | 0 | 0 | 0 |
| Eve\Models\Portfolio | portfolio | 6 | 0 | 0 | 0 |
| Eve\Models\Service | service | 9 | 0 | 0 | 0 |
| Eve\Models\Team | team | 8 | 0 | 0 | 0 |
| Eve\Models\Testimonial | testimonial | 11 | 0 | 0 | 0 |
| Eve\Models\Video | video | 0 | 0 | 1 | 0 |
| Lara\Common\Models\Cta | cta | 1 | 0 | 0 | 0 |
| Lara\Common\Models\Larawidget | larawidget | 4 | 0 | 0 | 0 |
| Lara\Common\Models\Page | page | 5 | 0 | 0 | 0 |
| Lara\Common\Models\Slider | slider | 7 | 0 | 0 | 0 |

### Media files on disk (/Users/hoeksms/Code/__cloudways/__migrate/anpak8246/public/assets/media)

| Folder | Content type | Rows | Only in _archive | Missing | Files in folder | Not referenced | Subfolders |
| --- | --- | --- | --- | --- | --- | --- | --- |
| _temp | — | 0 | 0 | 0 | 3 | 3 |  |
| application | yes | 5 | 0 | 0 | 5 | 0 |  |
| blog | yes | 13 | 0 | 0 | 13 | 0 |  |
| client | yes | 27 | 0 | 0 | 45 | 18 |  |
| contactform | yes | 0 | 0 | 0 | 0 | 0 |  |
| cta | yes | 1 | 0 | 0 | 1 | 0 |  |
| default | — | 0 | 0 | 0 | 1 | 1 |  |
| doc | yes | 12 | 5 | 0 | 7 | 0 |  |
| event | yes | 0 | 0 | 0 | 2 | 2 |  |
| files | — | 0 | 0 | 0 | 0 | 0 | shares |
| gallery | yes | 1 | 0 | 0 | 10 | 9 |  |
| images | — | 0 | 0 | 0 | 0 | 0 | shares |
| job | yes | 0 | 0 | 0 | 0 | 0 |  |
| larawidget | yes | 4 | 0 | 0 | 9 | 5 |  |
| location | yes | 0 | 0 | 0 | 0 | 0 |  |
| page | yes | 5 | 0 | 0 | 5 | 0 |  |
| portfolio | yes | 6 | 0 | 0 | 11 | 5 |  |
| service | yes | 9 | 0 | 0 | 12 | 3 |  |
| slider | yes | 7 | 0 | 0 | 12 | 5 |  |
| tag | — | 0 | 0 | 0 | 0 | 0 |  |
| team | yes | 8 | 0 | 0 | 8 | 0 |  |
| testimonial | yes | 11 | 0 | 0 | 17 | 6 |  |
| video | yes | 0 | 0 | 0 | 0 | 0 | custom |

### Other data

| What | Rows | Detail |
| --- | --- | --- |
| Users | 9 |  |
| Roles | 4 | member, editor, webmaster, administrator |
| Abilities | 116 |  |
| Languages | 2 | nl, en |
| Menus | 3 |  |
| Menu items | 34 | entity 12, form 3, page 11, parent 4, root 4 |
| Redirects | 0 |  |
| Taxonomies | 2 | category, tag |
| Tags | 82 | blog 7, cta 1, doc 9, event 5, gallery 3, larawidget 1, location 1, page 1, portfolio 8, service 10, setting 1, slider 11, team 17, testimonial 4, video 3 |
| Taggables | 37 |  |
| SEO rows | 57 |  |
| Opengraph rows | 41 |  |
| Layout rows | 5 |  |
| Related rows | 0 |  |
| Pageables | 4 |  |
| Sync rows | 0 |  |
| Settings | 45 | company 22, general 2, opengraph 5, system 16 |
| Translations | 1734 | lara-admin 1253, lara-common 19, lara-eve 327, lara-front 135 |
| Header tags | 31 |  |
| Blacklist | 18 |  |

### Findings (no v10 mapping, a change in behaviour, or inconsistent v8 data)

| # | Finding |
| --- | --- |
| 1 | Field type 'yesno': the yes/no choice becomes a toggle (classicform.myyesno). |
| 2 | cta.hook: 'homeparalax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 3 | cta.hook: 'homeparalax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 4 | cta.hook: 'homeparallax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 5 | 5 media row(s) of 'doc' point to a file that is only in doc/_archive (the import takes it from there). |
| 6 | Custom field location.latitude is defined 2 times. |
| 7 | Custom field location.longitude is defined 2 times. |

