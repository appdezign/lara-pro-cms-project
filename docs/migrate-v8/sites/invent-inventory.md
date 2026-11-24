
### Content types

| Key | Title | Group | v8 model | Table | Rows | Per language | Trashed | Fields | Relations |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| page | Pages | page | Lara\Common\Models\Page | lara_content_pages | 77 | en 22, nl 55 | 1 | 3 | 0 |
| blog | News | entity | Eve\Models\Blog | lara_content_blogs | 12 | nl 12 | 0 | 3 | 0 |
| event | Events | entity | Eve\Models\Event | lara_content_events | 6 | nl 6 | 0 | 5 | 0 |
| slider | Sliders | block | Lara\Common\Models\Slider | lara_blocks_sliders | 9 | en 4, nl 5 | 1 | 11 | 0 |
| larawidget | Widgets | block | Lara\Common\Models\Larawidget | lara_blocks_larawidgets | 28 | en 12, nl 16 | 2 | 15 | 0 |
| portfolio | Portfolio | entity | Eve\Models\Portfolio | lara_content_portfolios | 6 | nl 6 | 0 | 1 | 0 |
| cta | Call-to-action | block | Lara\Common\Models\Cta | lara_blocks_ctas | 4 | en 2, nl 2 | 0 | 6 | 0 |
| contactform | Contactform | form | Eve\Models\Contactform | lara_form_contactforms | 160 | — | 0 | 4 | 0 |
| doc | Docs | entity | Eve\Models\Doc | lara_content_docs | 2 | nl 2 | 0 | 0 | 0 |
| classicform | Classicforms | form | Eve\Models\Classicform | lara_form_classicforms | 11 | — | 0 | 4 | 0 |
| link | Links | entity | Eve\Models\Link | lara_content_links | 12 | nl 12 | 0 | 1 | 0 |

### Skipped content types (v8-migration.skip_entities; not built, data not imported)

| Key | Title | Records | Media rows | Tags | Taggables | SEO rows | Related rows | Menu items |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| team | Team | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| location | Locations | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| product | Products | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| gallery | Galleries | 0 | 0 | 2 | 0 | 0 | 0 | 0 |
| video | Videos | 0 | 0 | 2 | 0 | 0 | 0 | 0 |
| sponsor | Sponsors | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| story | Stories | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| testimonial | Testimonials | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| city | Cities | 0 | 0 | 0 | 0 | 0 | 0 | 0 |

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
| string | 20 | string | blog, classicform, contactform, cta, event, larawidget, link, page, portfolio, slider |
| selectone | 12 | core only | cta, larawidget, slider |
| boolean | 6 | toggle | blog, classicform, larawidget, page |
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
| hook | default | 8 | default |
| hook | between | 7 | after-slug |
| hook | after | 3 | after-last |
| state | enabled | 18 | enabled |

### Relations

| Entity | Type | Related | Foreign key | Filter | Same type in v10 |
| --- | --- | --- | --- | --- | --- |
| ? | hasMany | ? | city_id | no | yes |
| ? | belongsTo | ? | city_id | no | yes |

### Object relations and media settings

| Entity | Has | Related | Tags | Media (max) | Disks |
| --- | --- | --- | --- | --- | --- |
| page | seo opengraph layout sync | related relatable |  | images 24, videos 1, videofiles 1, files 24 | localdisk |
| blog | seo opengraph | related relatable |  | images 24, videos 1, videofiles 1, files 24 | localdisk |
| event |  | relatable |  |  | localdisk |
| slider |  |  | yes (default: home) | images 1 | localdisk |
| larawidget |  |  |  | images 1 | localdisk |
| portfolio |  | relatable |  | images 1 | localdisk |
| cta |  |  |  | images 1 | localdisk |
| contactform |  |  |  |  |  |
| doc |  | relatable |  | files 1 | localdisk |
| classicform |  |  |  |  |  |
| link |  |  |  |  | localdisk |

### Core field values (v8 data, translated, against the v10 options of the target site)

| Entity | v10 field | v8 column | Live records | Other records | Added to options by import-entities |
| --- | --- | --- | --- | --- | --- |
| larawidget | hook | hook | content_bottom (1) | content_bottom (1), footer1 (2), footer2 (2), home1 (2), home10 (2), home2 (2), home3 (2), home4 (2), home5 (2), home6 (2), home7 (2), home8 (2), home9 (2), parallax1 (1), parallax2 (1) | — |
| larawidget | iconalign | iconalign | — | left (12), top (6) | — |
| larawidget | resource_slug | relentkey | blogs (1) | teams (1) | dynamic (get_entity_resources): check the theme |
| larawidget | resource_term | term | none (1) | tag-1-1 (1) | dynamic (get_entity_terms): check the theme |
| larawidget | template | template | default (1) | default (25) | dynamic (get_widget_templates): check the theme |
| larawidget | type | type | module (1) | icon (18), module (1), parallax (2), text (6) | — |
| cta | hook | hook | — | homeparalax1 (1), homeparalax2 (1), homeparallax1 (1), homeparallax2 (1) | — |
| cta | overlaycolor | overlaycolor | — | black (2), brand (2) | — |
| cta | overlaytransp | overlaytransp | — | 40 (2), 60 (2) | — |
| cta | type | type | — | parallax (4) | — |
| slider | captiontype | captiontype | block (2), fade (2) | — | — |
| slider | overlaycolor | overlaycolor | black (2), brand1 (2), white (2) | black (1) | — |
| slider | overlaysize | overlaysize | block (2), full (4) | full (1) | — |
| slider | overlaytransp | overlaytransp | 80 (4) | — | — |
| slider | textposition | textposition | center (2), right (4) | center (1) | — |
| slider | type | type | caption (2), payoff (6) | payoff (1) | — |

### Front views

| Entity | Method | File | Type | Show tags | Paginate | Published |
| --- | --- | --- | --- | --- | --- | --- |
| page | show | show | _single | none | 0 | yes |
| blog | index | index | list | none | 5 | yes |
| blog | show | show | _single | none | 0 | yes |
| ? | index | index | grid3 | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| event | index | index | list | none | 0 | yes |
| event | show | show | _single | none | 0 | yes |
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| portfolio | index | index | list | none | 0 | yes |
| portfolio | show | show | _single | none | 0 | yes |
| contactform | form | form | _form | none | 0 | yes |
| ? | index | index | list | none | 0 | no |
| ? | show | show | _single | none | 0 | no |
| ? | index | index | grid3 | none | 12 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | list | none | 12 | yes |
| ? | show | show | _single | none | 0 | yes |
| doc | index | index | list | none | 0 | yes |
| doc | show | show | _single | none | 0 | yes |
| classicform | form | form | _form | none | 0 | yes |
| ? | index | index | list | none | 10 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | list | none | 0 | no |
| ? | show | show | _single | none | 0 | no |
| ? | index | index | grid3 | none | 6 | yes |
| ? | show | show | _single | none | 0 | yes |
| link | index | index | list | none | 0 | yes |

### Media rows per model

| v8 model | Entity | Images | Files | Videos | Videofiles |
| --- | --- | --- | --- | --- | --- |
| Eve\Models\Doc | doc | 0 | 2 | 0 | 0 |
| Eve\Models\Portfolio | portfolio | 6 | 0 | 0 | 0 |
| Lara\Common\Models\Page | page | 8 | 0 | 0 | 0 |

### Media files on disk (/Users/hoeksms/Code/__cloudways/__migrate/invent8246/.lara/media)

| Folder | Content type | Rows | Only in _archive | Missing | Files in folder | Not referenced | Subfolders |
| --- | --- | --- | --- | --- | --- | --- | --- |
| _temp | — | 0 | 0 | 0 | 0 | 0 |  |
| blog | yes | 0 | 0 | 0 | 0 | 0 |  |
| city | — | 0 | 0 | 0 | 0 | 0 |  |
| cta | yes | 0 | 0 | 0 | 0 | 0 |  |
| default | — | 0 | 0 | 0 | 1 | 1 |  |
| doc | yes | 2 | 1 | 0 | 1 | 0 |  |
| event | yes | 0 | 0 | 0 | 0 | 0 |  |
| gallery | — | 0 | 0 | 0 | 0 | 0 |  |
| images | — | 0 | 0 | 0 | 51 | 51 | alu_structuur, caravanstalling, expertise, groot_schadeherstel, kunststof_delen, nieuws, onderhoud_en_keuring, vlakke_beplating |
| larawidget | yes | 0 | 0 | 0 | 0 | 0 |  |
| link | yes | 0 | 0 | 0 | 0 | 0 |  |
| links | — | 0 | 0 | 0 | 0 | 0 |  |
| location | — | 0 | 0 | 0 | 0 | 0 |  |
| page | yes | 8 | 0 | 0 | 8 | 0 |  |
| portfolio | yes | 6 | 0 | 0 | 7 | 1 |  |
| slider | yes | 0 | 0 | 0 | 0 | 0 |  |
| sponsor | — | 0 | 0 | 0 | 0 | 0 |  |
| story | — | 0 | 0 | 0 | 0 | 0 |  |
| tag | — | 0 | 0 | 0 | 0 | 0 |  |
| team | — | 0 | 0 | 0 | 0 | 0 |  |
| video | — | 0 | 0 | 0 | 0 | 0 |  |

### Other data

| What | Rows | Detail |
| --- | --- | --- |
| Users | 6 |  |
| Roles | 4 | member, editor, webmaster, administrator |
| Abilities | 128 |  |
| Languages | 2 | nl, en |
| Menus | 3 |  |
| Menu items | 54 | entity 5, form 1, page 37, parent 7, root 4 |
| Redirects | 0 |  |
| Taxonomies | 2 | category, tag |
| Tags | 12 | blog 2, doc 2, event 2, gallery 2, portfolio 2, video 2 |
| Taggables | 0 |  |
| SEO rows | 61 |  |
| Opengraph rows | 27 |  |
| Layout rows | 7 |  |
| Related rows | 10 |  |
| Pageables | 0 |  |
| Sync rows | 0 |  |
| Settings | 38 | company 17, general 2, opengraph 5, system 14 |
| Translations | 1670 | lara-admin 1229, lara-common 19, lara-eve 293, lara-front 129 |
| Header tags | 20 |  |
| Blacklist | 55 |  |

### Findings (no v10 mapping, a change in behaviour, or inconsistent v8 data)

| # | Finding |
| --- | --- |
| 1 | larawidget.hook: 'parallax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 2 | larawidget.hook: 'parallax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 3 | larawidget.type: 'parallax' is only used by unpublished or trashed records (2x); not added as an option, the value is imported unchanged. |
| 4 | cta.hook: 'homeparalax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 5 | cta.hook: 'homeparalax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 6 | cta.hook: 'homeparallax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 7 | cta.hook: 'homeparallax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 8 | 1 media row(s) of 'doc' point to a file that is only in doc/_archive (the import takes it from there). |

8 finding(s): see the last section.
