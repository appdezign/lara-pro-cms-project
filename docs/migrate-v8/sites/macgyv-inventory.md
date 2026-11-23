
### Content types

| Key | Title | Group | v8 model | Table | Rows | Per language | Trashed | Fields | Relations |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| page | Pages | page | Lara\Common\Models\Page | lara_content_pages | 35 | en 3, nl 32 | 5 | 3 | 0 |
| blog | News | entity | Eve\Models\Blog | lara_content_blogs | 2 | nl 2 | 0 | 3 | 0 |
| slider | Sliders | block | Lara\Common\Models\Slider | lara_blocks_sliders | 6 | nl 6 | 0 | 12 | 0 |
| larawidget | Widgets | block | Lara\Common\Models\Larawidget | lara_blocks_larawidgets | 3 | nl 3 | 0 | 15 | 0 |
| cta | Call-to-action | block | Lara\Common\Models\Cta | lara_blocks_ctas | 6 | en 2, nl 4 | 2 | 6 | 0 |
| contactform | Contactform | form | Eve\Models\Contactform | lara_form_contactforms | 38 | — | 0 | 4 | 0 |
| gallery | Galleries | entity | Eve\Models\Gallery | lara_content_galleries | 2 | nl 2 | 0 | 0 | 0 |
| classicform | Classicforms | form | Eve\Models\Classicform | lara_form_classicforms | 18 | — | 0 | 8 | 0 |
| quoteform | Quoteforms | form | Eve\Models\Quoteform | lara_form_quoteforms | 2 | — | 0 | 5 | 0 |
| callmeform | Callmeforms | form | Eve\Models\Callmeform | lara_form_callmeforms | 12 | — | 0 | 2 | 0 |

### Skipped content types (v8-migration.skip_entities; not built, data not imported)

| Key | Title | Records | Media rows | Tags | Taggables | SEO rows | Related rows | Menu items |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| team | Team | 0 | 0 | 3 | 0 | 0 | 0 | 0 |
| event | Events | 0 | 0 | 4 | 0 | 0 | 0 | 0 |
| location | Locations | 0 | 0 | 1 | 0 | 0 | 0 | 0 |
| portfolio | Portfolio | 0 | 0 | 3 | 0 | 0 | 0 | 0 |
| product | Products | 0 | 0 | 3 | 0 | 0 | 0 | 0 |
| video | Videos | 0 | 0 | 3 | 0 | 0 | 0 | 0 |
| doc | Docs | 0 | 0 | 3 | 0 | 0 | 0 | 0 |
| sponsor | Sponsors | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| story | Stories | 0 | 0 | 2 | 0 | 0 | 0 | 0 |
| testimonial | Testimonials | 0 | 0 | 2 | 0 | 0 | 0 | 0 |
| city | Cities | 0 | 0 | 2 | 0 | 0 | 0 | 0 |
| service | Services | 0 | 0 | 2 | 0 | 0 | 0 | 0 |

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
| string | 21 | string | blog, callmeform, classicform, contactform, cta, larawidget, page, quoteform, slider |
| selectone | 14 | select | classicform, cta, larawidget, page, slider |
| boolean | 6 | toggle | blog, classicform, larawidget, page |
| text | 4 | textarea | classicform, contactform, quoteform, slider |
| email | 3 | email | classicform, contactform, quoteform |
| custom | 3 | core only | larawidget |
| intunsigned | 2 | core only | larawidget |
| radio | 1 | radio | classicform |
| yesno | 1 | toggle | classicform |
| date | 1 | date | classicform |
| selectone2one | 1 | core only | cta |
| mcemin | 1 | core only | slider |

### Custom field hooks and states (site content types)

| Kind | v8 value | Fields | v10 value |
| --- | --- | --- | --- |
| hook | default | 19 | default |
| hook | after | 2 | after-last |
| hook | between | 1 | after-slug |
| state | enabled | 21 | enabled |
| state | hidden | 1 | hidden |

### Relations

| Entity | Type | Related | Foreign key | Filter | Same type in v10 |
| --- | --- | --- | --- | --- | --- |
| ? | belongsTo | ? | location_id | yes | yes |
| ? | hasMany | ? | location_id | no | yes |
| ? | hasMany | ? | city_id | no | yes |
| ? | belongsTo | ? | city_id | no | yes |
| ? | hasMany | ? | location_id | no | yes |
| ? | belongsTo | ? | location_id | no | yes |

### Object relations and media settings

| Entity | Has | Related | Tags | Media (max) | Disks |
| --- | --- | --- | --- | --- | --- |
| page | seo opengraph layout sync | related relatable |  | images 24, videos 1, videofiles 1, files 24 | localdisk |
| blog | seo opengraph | related relatable |  | images 24, videos 1, videofiles 1, files 24 | localdisk |
| slider |  |  | yes (default: home) | images 1 | localdisk |
| larawidget |  |  |  | images 1 | localdisk |
| cta |  |  |  | images 1 | localdisk |
| contactform |  |  |  |  | localdisk |
| gallery |  |  |  | images 24 | localdisk |
| classicform |  |  |  |  | localdisk |
| quoteform |  |  |  |  | localdisk |
| callmeform |  |  |  |  | localdisk |

### Core field values (v8 data, translated, against the v10 options of the target site)

| Entity | v10 field | v8 column | Live records | Other records | Added to options by import-entities |
| --- | --- | --- | --- | --- | --- |
| larawidget | hook | hook | home1 (1), home2 (1), home3 (1) | — | — |
| larawidget | iconalign | iconalign | — | — | — |
| larawidget | resource_slug | relentkey | — | — | dynamic (get_entity_resources): check the theme |
| larawidget | resource_term | term | — | — | dynamic (get_entity_terms): check the theme |
| larawidget | template | template | home (3) | — | dynamic (get_widget_templates): check the theme |
| larawidget | type | type | text (3) | — | — |
| cta | hook | hook | contact (1), newsletter (1) | homeparalax1 (1), homeparalax2 (1), homeparallax1 (1), homeparallax2 (1) | — |
| cta | overlaycolor | overlaycolor | black (2) | black (2), brand (2) | — |
| cta | overlaytransp | overlaytransp | — | 40 (2), 60 (2) | — |
| cta | type | type | solid (2) | parallax (4) | — |
| slider | captiontype | captiontype | block (3) | — | — |
| slider | overlaycolor | overlaycolor | black (1) | — | — |
| slider | overlaysize | overlaysize | block (1) | — | — |
| slider | overlaytransp | overlaytransp | 40 (1) | — | — |
| slider | textposition | textposition | right (1) | — | — |
| slider | type | type | caption (3), image (2), payoff (1) | — | — |

### Front views

| Entity | Method | File | Type | Show tags | Paginate | Published |
| --- | --- | --- | --- | --- | --- | --- |
| page | show | show | _single | none | 0 | yes |
| blog | index | index | list | none | 6 | yes |
| blog | show | show | _single | none | 0 | yes |
| ? | index | index | grid4 | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| contactform | form | form | _form | none | 0 | yes |
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| gallery | index | index | grid3 | none | 12 | yes |
| gallery | show | show | _single | none | 0 | yes |
| ? | index | index | list | none | 12 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | list | _sortbytaxonomy | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| classicform | form | form | _form | none | 0 | yes |
| ? | index | index | list | none | 10 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | grid3 | none | 6 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | list | filterbytaxonomy | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| quoteform | form | form | _form | none | 0 | yes |
| callmeform | form | form | _form | none | 0 | yes |

### Media rows per model

| v8 model | Entity | Images | Files | Videos | Videofiles |
| --- | --- | --- | --- | --- | --- |
| Eve\Models\Blog | blog | 2 | 0 | 0 | 0 |
| Eve\Models\Gallery | gallery | 30 | 0 | 0 | 0 |
| Lara\Common\Models\Larawidget | larawidget | 3 | 0 | 0 | 0 |
| Lara\Common\Models\Page | page | 4 | 0 | 0 | 0 |
| Lara\Common\Models\Slider | slider | 6 | 0 | 0 | 0 |

### Media files on disk (/Users/hoeksms/Code/__cloudways/__migrate/macgyv8246/.lara/media)

| Folder | Content type | Rows | Only in _archive | Missing | Files in folder | Not referenced | Subfolders |
| --- | --- | --- | --- | --- | --- | --- | --- |
| _temp | — | 0 | 0 | 0 | 0 | 0 |  |
| blog | yes | 2 | 0 | 0 | 2 | 0 |  |
| callmeform | yes | 0 | 0 | 0 | 0 | 0 |  |
| city | — | 0 | 0 | 0 | 0 | 0 |  |
| contactform | yes | 0 | 0 | 0 | 0 | 0 |  |
| cta | yes | 0 | 0 | 0 | 0 | 0 |  |
| default | — | 0 | 0 | 0 | 1 | 1 |  |
| doc | — | 0 | 0 | 0 | 6 | 6 |  |
| event | — | 0 | 0 | 0 | 2 | 2 |  |
| gallery | yes | 30 | 0 | 0 | 39 | 9 |  |
| images | — | 0 | 0 | 0 | 0 | 0 | shares |
| larawidget | yes | 3 | 0 | 0 | 8 | 5 |  |
| location | — | 0 | 0 | 0 | 0 | 0 |  |
| page | yes | 4 | 0 | 0 | 12 | 8 |  |
| portfolio | — | 0 | 0 | 0 | 7 | 7 |  |
| product | — | 0 | 0 | 0 | 0 | 0 |  |
| quoteform | yes | 0 | 0 | 0 | 0 | 0 |  |
| service | — | 0 | 0 | 0 | 3 | 3 |  |
| slider | yes | 6 | 0 | 0 | 11 | 5 |  |
| sponsor | — | 0 | 0 | 0 | 0 | 0 |  |
| story | — | 0 | 0 | 0 | 0 | 0 |  |
| tag | — | 0 | 0 | 0 | 0 | 0 |  |
| team | — | 0 | 0 | 0 | 8 | 8 |  |
| testimonial | — | 0 | 0 | 0 | 6 | 6 |  |
| video | — | 0 | 0 | 0 | 0 | 0 |  |

### Other data

| What | Rows | Detail |
| --- | --- | --- |
| Users | 6 |  |
| Roles | 4 | member, editor, webmaster, administrator |
| Abilities | 128 |  |
| Languages | 2 | nl, en |
| Menus | 3 |  |
| Menu items | 22 | entity 2, form 3, page 11, parent 2, root 4 |
| Redirects | 0 |  |
| Taxonomies | 2 | category, tag |
| Tags | 48 | blog 4, city 2, cta 1, doc 3, event 4, gallery 2, larawidget 1, location 1, page 1, portfolio 3, product 3, service 2, setting 1, slider 10, story 2, team 3, testimonial 2, video 3 |
| Taggables | 6 |  |
| SEO rows | 17 |  |
| Opengraph rows | 17 |  |
| Layout rows | 26 |  |
| Related rows | 2 |  |
| Pageables | 3 |  |
| Sync rows | 0 |  |
| Settings | 43 | company 22, general 2, opengraph 5, system 14 |
| Translations | 1717 | lara-admin 1229, lara-common 19, lara-eve 336, lara-front 133 |
| Header tags | 14 |  |
| Blacklist | 27 |  |

### Findings (no v10 mapping, a change in behaviour, or inconsistent v8 data)

| # | Finding |
| --- | --- |
| 1 | Field type 'yesno': the yes/no choice becomes a toggle (classicform.myyesno). |
| 2 | cta.hook: 'homeparalax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 3 | cta.hook: 'homeparalax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 4 | cta.hook: 'homeparallax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 5 | cta.hook: 'homeparallax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |

5 finding(s): see the last section.
