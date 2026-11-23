
### Content types

| Key | Title | Group | v8 model | Table | Rows | Per language | Trashed | Fields | Relations |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| page | Pages | page | Lara\Common\Models\Page | lara_content_pages | 33 | en 2, nl 31 | 1 | 3 | 0 |
| blog | News | entity | Eve\Models\Blog | lara_content_blogs | 3 | nl 3 | 0 | 3 | 0 |
| team | Team | entity | Eve\Models\Team | lara_content_teams | 0 | 0 | 0 | 7 | 1 |
| event | Events | entity | Eve\Models\Event | lara_content_events | 7 | nl 7 | 0 | 5 | 1 |
| location | Locations | entity | Eve\Models\Location | lara_content_locations | 2 | nl 2 | 0 | 9 | 2 |
| slider | Sliders | block | Lara\Common\Models\Slider | lara_blocks_sliders | 0 | 0 | 0 | 12 | 0 |
| larawidget | Widgets | block | Lara\Common\Models\Larawidget | lara_blocks_larawidgets | 3 | nl 3 | 0 | 15 | 0 |
| cta | Call-to-action | block | Lara\Common\Models\Cta | lara_blocks_ctas | 1 | nl 1 | 0 | 6 | 0 |
| contactform | Contactform | form | Eve\Models\Contactform | lara_form_contactforms | 49 | — | 0 | 3 | 0 |
| gallery | Galleries | entity | Eve\Models\Gallery | lara_content_galleries | 1 | nl 1 | 0 | 0 | 0 |
| video | Videos | entity | Eve\Models\Video | lara_content_videos | 1 | nl 1 | 0 | 0 | 0 |
| doc | Docs | entity | Eve\Models\Doc | lara_content_docs | 1 | nl 1 | 0 | 0 | 0 |
| classicform | Classicforms | form | Eve\Models\Classicform | lara_form_classicforms | 0 | — | 0 | 8 | 0 |

### Skipped content types (v8-migration.skip_entities; not built, data not imported)

| Key | Title | Records | Media rows | Tags | Taggables | SEO rows | Related rows | Menu items |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| portfolio | Portfolio | 0 | 0 | 3 | 0 | 0 | 0 | 0 |
| product | Products | 0 | 0 | 3 | 0 | 0 | 0 | 0 |
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
| string | 27 | string | blog, classicform, contactform, cta, event, larawidget, location, page, slider, team |
| selectone | 14 | select | classicform, cta, larawidget, page, slider |
| boolean | 6 | toggle | blog, classicform, larawidget, page |
| text | 3 | textarea | classicform, contactform, slider |
| date | 3 | date | classicform, event |
| custom | 3 | core only | larawidget |
| email | 2 | email | classicform, contactform |
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
| hook | after | 16 | after-last |
| hook | default | 11 | default |
| hook | between | 6 | after-slug |
| hook | before | 2 | before-title |
| state | enabled | 30 | enabled |
| state | enabledif | 4 | enabledif |
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
| cta |  |  |  | images 1 | localdisk |
| contactform |  |  |  |  | localdisk |
| gallery |  |  | yes | images 24 | localdisk |
| video | seo |  | yes | videos 1 | localdisk |
| doc |  | relatable | yes | files 1 |  |
| classicform |  |  |  |  | localdisk |

### Core field values (v8 data, translated, against the v10 options of the target site)

| Entity | v10 field | v8 column | Live records | Other records | Added to options by import-entities |
| --- | --- | --- | --- | --- | --- |
| larawidget | hook | hook | footer1 (1), footer2 (1), footer3 (1) | — | — |
| larawidget | iconalign | iconalign | — | — | — |
| larawidget | resource_slug | relentkey | — | — | dynamic (get_entity_resources): check the theme |
| larawidget | resource_term | term | — | — | dynamic (get_entity_terms): check the theme |
| larawidget | template | template | footer1 (1), footer2 (1), footer3 (1) | — | dynamic (get_widget_templates): check the theme |
| larawidget | type | type | text (3) | — | — |
| cta | hook | hook | contact (1) | — | — |
| cta | overlaycolor | overlaycolor | brand (1) | — | — |
| cta | overlaytransp | overlaytransp | 30 (1) | — | — |
| cta | type | type | solid (1) | — | — |
| slider | captiontype | captiontype | — | — | — |
| slider | overlaycolor | overlaycolor | — | — | — |
| slider | overlaysize | overlaysize | — | — | — |
| slider | overlaytransp | overlaytransp | — | — | — |
| slider | textposition | textposition | — | — | — |
| slider | type | type | — | — | — |

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
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| gallery | index | index | grid3 | none | 12 | yes |
| gallery | show | show | _single | none | 0 | yes |
| video | index | index | list | none | 12 | yes |
| video | show | show | _single | none | 0 | yes |
| doc | index | index | list | _sortbytaxonomy | 0 | yes |
| doc | show | show | _single | none | 0 | yes |
| classicform | form | form | _form | none | 0 | yes |
| ? | index | index | list | none | 10 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | grid3 | none | 6 | yes |
| ? | show | show | _single | none | 0 | yes |
| ? | index | index | list | filterbytaxonomy | 0 | yes |
| ? | show | show | _single | none | 0 | yes |

### Media rows per model

| v8 model | Entity | Images | Files | Videos | Videofiles |
| --- | --- | --- | --- | --- | --- |
| Eve\Models\Blog | blog | 3 | 0 | 0 | 0 |
| Eve\Models\Gallery | gallery | 4 | 0 | 0 | 0 |
| Eve\Models\Video | video | 0 | 0 | 1 | 0 |
| Lara\Common\Models\Page | page | 27 | 0 | 0 | 0 |

### Media files on disk (/Users/hoeksms/Code/__cloudways/__migrate/doormode8246/.lara/media)

| Folder | Content type | Rows | Only in _archive | Missing | Files in folder | Not referenced | Subfolders |
| --- | --- | --- | --- | --- | --- | --- | --- |
| _temp | — | 0 | 0 | 0 | 0 | 0 |  |
| blog | yes | 3 | 0 | 0 | 13 | 10 |  |
| city | — | 0 | 0 | 0 | 0 | 0 |  |
| contactform | yes | 0 | 0 | 0 | 0 | 0 |  |
| cta | yes | 0 | 0 | 0 | 0 | 0 |  |
| default | — | 0 | 0 | 0 | 1 | 1 |  |
| doc | yes | 0 | 0 | 0 | 6 | 6 |  |
| event | yes | 0 | 0 | 0 | 2 | 2 |  |
| gallery | yes | 4 | 0 | 0 | 13 | 9 |  |
| images | — | 0 | 0 | 0 | 0 | 0 | shares |
| larawidget | yes | 0 | 0 | 0 | 5 | 5 |  |
| location | yes | 0 | 0 | 0 | 0 | 0 |  |
| page | yes | 27 | 0 | 0 | 35 | 8 |  |
| portfolio | — | 0 | 0 | 0 | 7 | 7 |  |
| product | — | 0 | 0 | 0 | 0 | 0 |  |
| service | — | 0 | 0 | 0 | 3 | 3 |  |
| slider | yes | 0 | 0 | 0 | 5 | 5 |  |
| sponsor | — | 0 | 0 | 0 | 0 | 0 |  |
| story | — | 0 | 0 | 0 | 0 | 0 |  |
| tag | — | 0 | 0 | 0 | 0 | 0 |  |
| team | yes | 0 | 0 | 0 | 8 | 8 |  |
| testimonial | — | 0 | 0 | 0 | 6 | 6 |  |
| video | yes | 0 | 0 | 0 | 0 | 0 |  |

### Other data

| What | Rows | Detail |
| --- | --- | --- |
| Users | 6 |  |
| Roles | 4 | member, editor, webmaster, administrator |
| Abilities | 120 |  |
| Languages | 2 | nl, en |
| Menus | 3 |  |
| Menu items | 25 | entity 3, form 1, page 17, root 4 |
| Redirects | 0 |  |
| Taxonomies | 2 | category, tag |
| Tags | 49 | blog 4, city 2, cta 1, doc 3, event 4, gallery 3, larawidget 1, location 1, page 1, portfolio 3, product 3, service 2, setting 1, slider 10, story 2, team 3, testimonial 2, video 3 |
| Taggables | 0 |  |
| SEO rows | 24 |  |
| Opengraph rows | 22 |  |
| Layout rows | 14 |  |
| Related rows | 0 |  |
| Pageables | 0 |  |
| Sync rows | 0 |  |
| Settings | 43 | company 22, general 2, opengraph 5, system 14 |
| Translations | 1724 | lara-admin 1234, lara-common 21, lara-eve 360, lara-front 109 |
| Header tags | 25 |  |
| Blacklist | 20 |  |

### Findings (no v10 mapping, a change in behaviour, or inconsistent v8 data)

| # | Finding |
| --- | --- |
| 1 | Field type 'yesno': the yes/no choice becomes a toggle (classicform.myyesno). |
| 2 | Custom field location.latitude is defined 2 times. |
| 3 | Custom field location.longitude is defined 2 times. |

3 finding(s): see the last section.
