
### Content types

| Key | Title | Group | v8 model | Table | Rows | Per language | Trashed | Fields | Relations |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| page | Pages | page | Lara\Common\Models\Page | lara_content_pages | 83 | de 6, en 2, nl 75 | 14 | 3 | 0 |
| blog | News | entity | Eve\Models\Blog | lara_content_blogs | 289 | de 1, nl 288 | 3 | 3 | 0 |
| event | Events | entity | Eve\Models\Event | lara_content_events | 29 | nl 29 | 26 | 5 | 1 |
| slider | Sliders | block | Lara\Common\Models\Slider | lara_blocks_sliders | 17 | en 4, nl 13 | 6 | 11 | 0 |
| larawidget | Widgets | block | Lara\Common\Models\Larawidget | lara_blocks_larawidgets | 5 | de 1, nl 4 | 0 | 15 | 0 |
| cta | Call-to-action | block | Lara\Common\Models\Cta | lara_blocks_ctas | 5 | en 2, nl 3 | 1 | 6 | 0 |
| contactform | Contactform | form | Eve\Models\Contactform | lara_form_contactforms | 153 | — | 0 | 7 | 0 |
| gallery | Galleries | entity | Eve\Models\Gallery | lara_content_galleries | 86 | nl 86 | 3 | 0 | 0 |
| video | Videos | entity | Eve\Models\Video | lara_content_videos | 2 | nl 2 | 0 | 1 | 0 |
| doc | Docs | entity | Eve\Models\Doc | lara_content_docs | 45 | de 3, nl 42 | 16 | 1 | 0 |
| classicform | Classicforms | form | Eve\Models\Classicform | lara_form_classicforms | 11 | — | 0 | 4 | 0 |
| link | Links | entity | Eve\Models\Link | lara_content_links | 13 | nl 13 | 0 | 1 | 0 |

### Skipped content types (v8-migration.skip_entities; not built, data not imported)

| Key | Title | Records | Media rows | Tags | Taggables | SEO rows | Related rows | Menu items |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| location | Locations | 6 | 0 | 1 | 0 | 0 | 0 | 0 |
| impression | Impressions | 3 | 21 | 0 | 0 | 0 | 0 | 0 |
| faq | Faqs | 8 | 6 | 0 | 0 | 0 | 0 | 0 |
| charter | Charters | 4 | 4 | 0 | 0 | 0 | 0 | 0 |
| service | Services | 2 | 2 | 0 | 0 | 0 | 0 | 0 |
| trip | Trips | 1 | 1 | 0 | 0 | 0 | 0 | 0 |
| place | Places | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| bridge | Bridges | 3 | 0 | 0 | 0 | 0 | 0 | 0 |

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
| string | 22 | string | blog, classicform, contactform, cta, event, larawidget, link, page, slider, video |
| selectone | 13 | core only | cta, larawidget, page, slider |
| boolean | 7 | toggle | blog, classicform, doc, larawidget, page |
| custom | 3 | core only | larawidget |
| text | 2 | textarea | contactform, slider |
| date | 2 | date | event |
| time | 2 | time | event |
| intunsigned | 2 | core only | larawidget |
| integer | 1 | number | classicform |
| email | 1 | email | classicform |
| selectone2one | 1 | core only | cta |
| mcemin | 1 | core only | slider |

### Custom field hooks and states (site content types)

| Kind | v8 value | Fields | v10 value |
| --- | --- | --- | --- |
| hook | default | 11 | default |
| hook | after | 6 | after-last |
| hook | between | 5 | after-slug |
| state | enabled | 22 | enabled |

### Relations

| Entity | Type | Related | Foreign key | Filter | Same type in v10 |
| --- | --- | --- | --- | --- | --- |
| event | belongsTo | ? | location_id | yes | yes |
| ? | hasMany | event | location_id | no | yes |
| ? | hasMany | ? | location_id | no | yes |
| ? | belongsTo | ? | location_id | no | yes |

### Object relations and media settings

| Entity | Has | Related | Tags | Media (max) | Disks |
| --- | --- | --- | --- | --- | --- |
| page | seo opengraph layout sync | related relatable |  | images 24, videos 1, videofiles 1, files 24 | localdisk |
| blog | seo opengraph | related relatable | yes | images 24, videos 1, videofiles 1, files 24 | localdisk |
| event |  |  | yes |  | localdisk |
| slider |  |  | yes (default: home) | images 1 | localdisk |
| larawidget |  |  |  | images 1 | localdisk |
| cta |  |  |  | images 1 | localdisk |
| contactform |  |  |  |  | localdisk |
| gallery |  |  | yes | images 24 | localdisk |
| video | seo |  | yes |  | localdisk |
| doc |  | relatable | yes | files 1 | localdisk |
| classicform |  |  |  |  |  |
| link |  | relatable |  |  | localdisk |

### Core field values (v8 data, translated, against the v10 options of the target site)

| Entity | v10 field | v8 column | Live records | Other records | Added to options by import-entities |
| --- | --- | --- | --- | --- | --- |
| larawidget | hook | hook | footer3 (2), home1 (1), home2 (1), home3 (1) | — | — |
| larawidget | iconalign | iconalign | left (3) | — | — |
| larawidget | resource_slug | relentkey | — | — | dynamic (get_entity_resources): check the theme |
| larawidget | resource_term | term | — | — | dynamic (get_entity_terms): check the theme |
| larawidget | template | template | footer (2), home (3) | — | dynamic (get_widget_templates): check the theme |
| larawidget | type | type | text (5) | — | — |
| cta | hook | hook | homeparallax2 (1) | homeparalax1 (1), homeparalax2 (1), homeparallax1 (1), homeparallax2 (1) | homeparallax2 |
| cta | overlaycolor | overlaycolor | black (1) | black (2), brand (2) | — |
| cta | overlaytransp | overlaytransp | — | 20 (1), 40 (1), 60 (2) | — |
| cta | type | type | solid (1) | parallax (4) | — |
| slider | captiontype | captiontype | block (2), fade (1) | fade (1) | — |
| slider | overlaycolor | overlaycolor | black (2), brand1 (1), white (1) | black (1), brand1 (1), white (1) | — |
| slider | overlaysize | overlaysize | block (1), full (3) | block (1), full (2) | — |
| slider | overlaytransp | overlaytransp | 80 (2) | 80 (2) | — |
| slider | textposition | textposition | center (2), right (2) | center (1), right (2) | — |
| slider | type | type | caption (1), image (6), payoff (3) | caption (1), image (3), payoff (3) | — |

### Front views

| Entity | Method | File | Type | Show tags | Paginate | Published |
| --- | --- | --- | --- | --- | --- | --- |
| page | show | show | _single | none | 0 | yes |
| blog | index | index | list | none | 8 | yes |
| blog | show | show | _single | none | 0 | yes |
| event | index | index | list | none | 0 | yes |
| event | show | show | _single | none | 0 | yes |
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| contactform | form | form | _form | none | 0 | yes |
| gallery | index | index | grid3 | none | 12 | yes |
| gallery | show | show | _single | none | 0 | yes |
| video | index | index | list | none | 12 | yes |
| video | show | show | _single | none | 0 | yes |
| doc | index | index | list | _sortbytaxonomy | 0 | yes |
| doc | show | show | _single | none | 0 | yes |
| classicform | form | form | _form | none | 0 | yes |
| link | index | index | list | none | 0 | yes |
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |

### Media rows per model

| v8 model | Entity | Images | Files | Videos | Videofiles |
| --- | --- | --- | --- | --- | --- |
| Eve\Models\Blog | blog | 353 | 12 | 2 | 0 |
| Eve\Models\Charter | charter | 4 | 0 | 0 | 0 |
| Eve\Models\City | ? | 10 | 1 | 0 | 0 |
| Eve\Models\Doc | doc | 0 | 100 | 0 | 0 |
| Eve\Models\Faq | faq | 6 | 0 | 0 | 0 |
| Eve\Models\Gallery | gallery | 1487 | 0 | 0 | 0 |
| Eve\Models\Impression | impression | 21 | 0 | 0 | 0 |
| Eve\Models\Portfolio | ? | 17 | 0 | 0 | 0 |
| Eve\Models\Post | ? | 13 | 0 | 0 | 0 |
| Eve\Models\Service | service | 2 | 0 | 0 | 0 |
| Eve\Models\Sponsor | ? | 5 | 0 | 0 | 0 |
| Eve\Models\Team | ? | 30 | 8 | 0 | 0 |
| Eve\Models\Trip | trip | 1 | 0 | 0 | 0 |
| Lara\Common\Models\Cta | cta | 3 | 0 | 0 | 0 |
| Lara\Common\Models\Larawidget | larawidget | 4 | 0 | 0 | 0 |
| Lara\Common\Models\Page | page | 18 | 6 | 1 | 0 |
| Lara\Common\Models\Slider | slider | 9 | 0 | 0 | 0 |
| Lara\Common\Models\Tag | tag | 4 | 0 | 0 | 0 |

### Media files on disk (/Users/hoeksms/Code/__cloudways/__migrate/hwn8246/.lara/production_switch/media)

| Folder | Content type | Rows | Only in _archive | Missing | Files in folder | Not referenced | Subfolders |
| --- | --- | --- | --- | --- | --- | --- | --- |
| _temp | — | 0 | 0 | 0 | 10 | 10 |  |
| blog | yes | 365 | 0 | 3 | 362 | 0 |  |
| bridge | — | 0 | 0 | 0 | 0 | 0 |  |
| charter | — | 0 | 0 | 0 | 4 | 4 |  |
| contactform | yes | 0 | 0 | 0 | 0 | 0 |  |
| cta | yes | 3 | 0 | 2 | 3 | 2 |  |
| default | — | 0 | 0 | 0 | 1 | 1 |  |
| doc | yes | 50 | 13 | 34 | 27 | 0 |  |
| event | yes | 0 | 0 | 0 | 0 | 0 |  |
| faq | — | 0 | 0 | 0 | 7 | 7 |  |
| files | — | 0 | 0 | 0 | 0 | 0 | shares |
| gallery | yes | 1487 | 0 | 0 | 1655 | 168 |  |
| images | — | 0 | 0 | 0 | 91 | 91 | activiteiten, de_bron, de_welle, geschiedenis, home_widgets, hwn, nieuwjaarsbijeenkomst_2014, nieuws, samen_onderweg_2011, shares |
| impression | — | 0 | 0 | 0 | 20 | 20 |  |
| larawidget | yes | 4 | 0 | 1 | 3 | 0 |  |
| link | yes | 0 | 0 | 0 | 0 | 0 |  |
| location | — | 0 | 0 | 0 | 0 | 0 |  |
| page | yes | 24 | 1 | 7 | 16 | 0 |  |
| place | — | 0 | 0 | 0 | 0 | 0 |  |
| service | — | 0 | 0 | 0 | 2 | 2 |  |
| slider | yes | 9 | 0 | 4 | 17 | 12 |  |
| tag | — | 4 | 0 | 4 | 0 | 0 |  |
| trip | — | 0 | 0 | 0 | 1 | 1 |  |

### Other data

| What | Rows | Detail |
| --- | --- | --- |
| Users | 10 |  |
| Roles | 5 | member, editor, webmaster, appmaster, administrator |
| Abilities | 128 |  |
| Languages | 2 | nl, de |
| Menus | 3 |  |
| Menu items | 64 | entity 13, form 2, page 36, parent 8, root 5 |
| Redirects | 0 |  |
| Taxonomies | 2 | category, tag |
| Tags | 52 | blog 9, cta 1, doc 12, event 5, gallery 5, larawidget 1, location 1, page 1, setting 1, slider 11, video 5 |
| Taggables | 178 |  |
| SEO rows | 132 |  |
| Opengraph rows | 117 |  |
| Layout rows | 18 |  |
| Related rows | 56 |  |
| Pageables | 3 |  |
| Sync rows | 0 |  |
| Settings | 62 | company 38, general 2, opengraph 5, system 17 |
| Translations | 1656 | lara-admin 1243, lara-common 19, lara-eve 255, lara-front 139 |
| Header tags | 26 |  |
| Blacklist | 27 |  |

### Findings (no v10 mapping, a change in behaviour, or inconsistent v8 data)

| # | Finding |
| --- | --- |
| 1 | lara_content_pages: 2 row(s) in language 'en', which is not in lara_sys_languages. |
| 2 | lara_blocks_sliders: 4 row(s) in language 'en', which is not in lara_sys_languages. |
| 3 | lara_blocks_ctas: 2 row(s) in language 'en', which is not in lara_sys_languages. |
| 4 | Content type 'location' is skipped: 6 record(s), 0 media row(s) and 0 menu item(s) are not imported. |
| 5 | Content type 'impression' is skipped: 3 record(s), 21 media row(s) and 0 menu item(s) are not imported. |
| 6 | Content type 'faq' is skipped: 8 record(s), 6 media row(s) and 0 menu item(s) are not imported. |
| 7 | Content type 'charter' is skipped: 4 record(s), 4 media row(s) and 0 menu item(s) are not imported. |
| 8 | Content type 'service' is skipped: 2 record(s), 2 media row(s) and 0 menu item(s) are not imported. |
| 9 | Content type 'trip' is skipped: 1 record(s), 1 media row(s) and 0 menu item(s) are not imported. |
| 10 | Content type 'bridge' is skipped: 3 record(s), 0 media row(s) and 0 menu item(s) are not imported. |
| 11 | cta.hook: 'homeparallax2' is used by 1 live record(s) and is not a v10 option; import-entities adds it to ctas.hook. |
| 12 | cta.hook: 'homeparalax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 13 | cta.hook: 'homeparalax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 14 | cta.hook: 'homeparallax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 15 | lara_object_images: 1 row(s) of Lara\Common\Models\Larawidget belong to a record that no longer exists. |
| 16 | lara_object_images: 6 row(s) of Lara\Common\Models\Page belong to a record that no longer exists. |
| 17 | lara_object_files: 2 row(s) of Eve\Models\Blog belong to a record that no longer exists. |
| 18 | lara_object_files: 68 row(s) of Eve\Models\Doc belong to a record that no longer exists. |
| 19 | lara_object_videos: 2 row(s) of Eve\Models\Blog belong to a record that no longer exists. |
| 20 | lara_object_videos: 1 row(s) of Lara\Common\Models\Page belong to a record that no longer exists. |
| 21 | Media rows of 'Eve\Models\City', which is not the model of any v8 entity. |
| 22 | Media rows of 'Eve\Models\Portfolio', which is not the model of any v8 entity. |
| 23 | Media rows of 'Eve\Models\Post', which is not the model of any v8 entity. |
| 24 | Media rows of 'Eve\Models\Sponsor', which is not the model of any v8 entity. |
| 25 | Media rows of 'Eve\Models\Team', which is not the model of any v8 entity. |
| 26 | 3 media row(s) of 'blog' point to a file that is missing (also not in _archive): /Users/hoeksms/Code/__cloudways/__migrate/hwn8246/.lara/production_switch/media/blog. |
| 27 | 2 media row(s) of 'cta' point to a file that is missing (also not in _archive): /Users/hoeksms/Code/__cloudways/__migrate/hwn8246/.lara/production_switch/media/cta. |
| 28 | 34 media row(s) of 'doc' point to a file that is missing (also not in _archive): /Users/hoeksms/Code/__cloudways/__migrate/hwn8246/.lara/production_switch/media/doc. |
| 29 | 13 media row(s) of 'doc' point to a file that is only in doc/_archive (the import takes it from there). |
| 30 | 1 media row(s) of 'larawidget' point to a file that is missing (also not in _archive): /Users/hoeksms/Code/__cloudways/__migrate/hwn8246/.lara/production_switch/media/larawidget. |
| 31 | 7 media row(s) of 'page' point to a file that is missing (also not in _archive): /Users/hoeksms/Code/__cloudways/__migrate/hwn8246/.lara/production_switch/media/page. |
| 32 | 1 media row(s) of 'page' point to a file that is only in page/_archive (the import takes it from there). |
| 33 | 4 media row(s) of 'slider' point to a file that is missing (also not in _archive): /Users/hoeksms/Code/__cloudways/__migrate/hwn8246/.lara/production_switch/media/slider. |
| 34 | 4 media row(s) of 'tag' point to a file that is missing (also not in _archive): /Users/hoeksms/Code/__cloudways/__migrate/hwn8246/.lara/production_switch/media/tag. |

34 finding(s): see the last section.
