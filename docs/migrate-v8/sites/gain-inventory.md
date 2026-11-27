
### Content types

| Key | Title | Group | v8 model | Table | Rows | Per language | Trashed | Fields | Relations |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| page | Pages | page | Lara\Common\Models\Page | lara_content_pages | 115 | en 2, nl 113 | 33 | 3 | 0 |
| blog | News | entity | Eve\Models\Blog | lara_content_blogs | 177 | nl 177 | 12 | 3 | 0 |
| event | Events | entity | Eve\Models\Event | lara_content_events | 67 | nl 67 | 5 | 7 | 2 |
| location | Locations | entity | Eve\Models\Location | lara_content_locations | 43 | nl 43 | 0 | 7 | 1 |
| slider | Sliders | block | Lara\Common\Models\Slider | lara_blocks_sliders | 21 | nl 21 | 3 | 19 | 0 |
| larawidget | Widgets | block | Lara\Common\Models\Larawidget | lara_blocks_larawidgets | 10 | nl 10 | 0 | 15 | 0 |
| portfolio | Portfolio | entity | Eve\Models\Portfolio | lara_content_portfolios | 17 | nl 17 | 3 | 17 | 1 |
| cta | Call-to-action | block | Lara\Common\Models\Cta | lara_blocks_ctas | 2 | nl 2 | 2 | 6 | 0 |
| gallery | Galleries | entity | Eve\Models\Gallery | lara_content_galleries | 1 | nl 1 | 0 | 0 | 0 |
| video | Videos | entity | Eve\Models\Video | lara_content_videos | 3 | nl 3 | 1 | 0 | 0 |
| doc | Docs | entity | Eve\Models\Doc | lara_content_docs | 13 | nl 13 | 10 | 0 | 0 |
| testimonial | Testimonials | entity | Eve\Models\Testimonial | lara_content_testimonials | 12 | nl 12 | 1 | 1 | 1 |
| organizer | Organizers | entity | Eve\Models\Organizer | lara_content_organizers | 28 | nl 28 | 5 | 0 | 1 |
| faq | Faqs | entity | Eve\Models\Faq | lara_content_faqs | 23 | nl 23 | 0 | 0 | 0 |
| experience | Experiences | entity | Eve\Models\Experience | lara_content_experiences | 23 | nl 23 | 0 | 0 | 0 |
| activity | Activities | entity | Eve\Models\Activity | lara_content_activities | 6 | nl 6 | 0 | 0 | 0 |
| step | Steps | entity | Eve\Models\Step | lara_content_steps | 11 | nl 11 | 0 | 0 | 0 |
| docpro | Docpros | entity | Eve\Models\Docpro | lara_content_docpros | 75 | nl 75 | 6 | 1 | 0 |
| interview | Interviews | entity | Eve\Models\Interview | lara_content_interviews | 5 | nl 5 | 2 | 24 | 0 |

### Skipped content types (v8-migration.skip_entities; not built, data not imported)

| Key | Title | Records | Media rows | Tags | Taggables | SEO rows | Related rows | Menu items |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| team | Team | 0 | 0 | 13 | 0 | 0 | 0 | 0 |
| contactform | Contactform | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| product | Products | 0 | 0 | 8 | 0 | 0 | 0 | 0 |
| sponsor | Sponsors | 6 | 5 | 0 | 0 | 6 | 0 | 0 |
| classicform | Classicforms | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| member | Members | 21 | 2 | 6 | 2 | 3 | 0 | 1 |

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
| string | 43 | string | blog, cta, event, interview, larawidget, location, page, portfolio, slider, testimonial |
| custom | 24 | textarea | docpro, interview, larawidget |
| selectone | 15 | core only | cta, larawidget, page, slider |
| boolean | 6 | toggle | blog, larawidget, page, slider |
| mcemin | 4 | richeditormin | interview, slider |
| date | 2 | date | event |
| time | 2 | time | event |
| intunsigned | 2 | core only | larawidget |
| selectone2one | 1 | core only | cta |
| geolocation | 1 | geolocation | location |
| decimal108 | 1 | latitude_10_8 | location |
| decimal118 | 1 | longitude_11_8 | location |
| text | 1 | core only | slider |

### Custom field hooks and states (site content types)

| Kind | v8 value | Fields | v10 value |
| --- | --- | --- | --- |
| hook | after | 51 | after-last |
| hook | between | 8 | after-slug |
| hook | before | 1 | before-title |
| state | enabled | 57 | enabled |
| state | enabledif | 2 | enabledif |
| state | hidden | 1 | hidden |

### Relations

| Entity | Type | Related | Foreign key | Filter | Same type in v10 |
| --- | --- | --- | --- | --- | --- |
| event | belongsTo | location | location_id | no | yes |
| location | hasMany | event | location_id | no | yes |
| organizer | hasMany | event | organizer_id | no | yes |
| event | belongsTo | organizer | organizer_id | yes | yes |
| portfolio | hasMany | testimonial | portfolio_id | no | yes |
| testimonial | belongsTo | portfolio | portfolio_id | no | yes |

### Object relations and media settings

| Entity | Has | Related | Tags | Media (max) | Disks |
| --- | --- | --- | --- | --- | --- |
| page | seo opengraph layout sync | related relatable |  | images 24, videos 1, videofiles 1, files 24 | localdisk |
| blog | seo opengraph | related relatable | yes | images 24, videos 1 | localdisk |
| event | seo | relatable | yes |  | localdisk |
| location |  |  |  |  | localdisk |
| slider |  |  | yes (default: home) | images 1 | localdisk |
| larawidget |  |  |  | images 1 | localdisk |
| portfolio | seo | relatable | yes |  | localdisk |
| cta |  |  |  | images 1 | localdisk |
| gallery | seo | related relatable | yes | images 100 | localdisk |
| video | seo | related relatable | yes | videos 1 | localdisk |
| doc | seo | related relatable | yes | files 1 | localdisk |
| testimonial | seo |  |  | images 1 | localdisk |
| organizer |  |  |  |  | localdisk |
| faq |  |  |  |  | localdisk |
| experience | seo |  | yes | images 24 | localdisk |
| activity |  |  |  | images 1 | localdisk |
| step |  |  |  |  | localdisk |
| docpro |  | relatable |  | files 1 | localdisk |
| interview | seo | relatable |  | images 24, videos 1 | localdisk |

### Core field values (v8 data, translated, against the v10 options of the target site)

| Entity | v10 field | v8 column | Live records | Other records | Added to options by import-entities |
| --- | --- | --- | --- | --- | --- |
| larawidget | hook | hook | content_bottom (5), footer1 (1), footer3 (1), footer4 (1), home1 (1), popup1 (1) | — | — |
| larawidget | iconalign | iconalign | left (1) | — | — |
| larawidget | resource_slug | relentkey | portfolios (2) | — | dynamic (get_entity_resources): check the theme |
| larawidget | resource_term | term | none (2) | — | dynamic (get_entity_terms): check the theme |
| larawidget | template | template | contact (1), footer (1), footer1 (1), footer3 (1), home1 (1), leden (1), popup1 (1), strategie (1), testimonial (1), visie (1) | — | dynamic (get_widget_templates): check the theme |
| larawidget | type | type | icon (1), landing (4), module (2), text (3) | — | — |
| cta | hook | hook | — | homeparalax1 (1), homeparalax2 (1) | — |
| cta | overlaycolor | overlaycolor | — | black (1), brand (1) | — |
| cta | overlaytransp | overlaytransp | — | 40 (1), 60 (1) | — |
| cta | type | type | — | parallax (2) | — |
| slider | captiontype | captiontype | block (1) | fade (1) | — |
| slider | overlaycolor | overlaycolor | black (8) | brand1 (1), white (1) | — |
| slider | overlaysize | overlaysize | full (8) | block (1), full (1) | — |
| slider | overlaytransp | overlaytransp | 30 (8) | 80 (2) | — |
| slider | qoverlay | qoverlay | 25 (1) | — | — |
| slider | qtextposition | qtextposition | center (2), left (6), right (1) | — | — |
| slider | textposition | textposition | center (8) | right (2) | — |
| slider | type | type | image (2), payoff (7), quote (9) | caption (1), payoff (2) | — |

### Front views

| Entity | Method | File | Type | Show tags | Paginate | Published |
| --- | --- | --- | --- | --- | --- | --- |
| page | show | show | _single | none | 0 | yes |
| blog | index | index | list | filterbytaxonomy | 5 | yes |
| blog | show | show | _single | none | 0 | yes |
| ? | index | index | grid4 | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| event | index | index | list | none | 0 | yes |
| event | show | show | _single | none | 0 | yes |
| portfolio | index | index | list | none | 0 | yes |
| portfolio | show | show | _single | none | 0 | yes |
| ? | form | form | _form | none | 0 | yes |
| ? | index | index | list | none | 0 | yes |
| ? | show | show | _single | none | 0 | yes |
| gallery | index | index | grid3 | none | 12 | yes |
| gallery | show | show | _single | none | 0 | yes |
| video | index | index | grid3 | none | 12 | yes |
| video | show | show | _single | none | 0 | yes |
| doc | index | index | list | _sortbytaxonomy | 0 | yes |
| doc | show | show | _single | none | 0 | yes |
| ? | form | form | _form | none | 0 | yes |
| testimonial | index | index | list | none | 0 | yes |
| testimonial | show | show | _single | none | 0 | yes |
| faq | index | index | list | none | 0 | yes |
| faq | show | show | _single | none | 0 | yes |
| experience | index | index | grid2 | filterbytaxonomy | 10 | yes |
| experience | show | show | _single | none | 0 | yes |
| activity | index | index | grid3 | none | 0 | yes |
| step | index | index | list | none | 0 | yes |
| step | show | show | _single | none | 0 | yes |
| docpro | index | index | list | none | 10 | yes |
| docpro | show | show | _single | none | 0 | yes |
| interview | index | index | list | none | 0 | yes |
| interview | show | show | _single | none | 0 | yes |
| ? | create | create | _single | none | 0 | yes |
| ? | edit | edit | _single | none | 0 | yes |
| ? | index | index | list | _sortbytaxonomy | 0 | yes |
| ? | show | show | _single | none | 0 | yes |

### Media rows per model

| v8 model | Entity | Images | Files | Videos | Videofiles |
| --- | --- | --- | --- | --- | --- |
| Eve\Models\Activity | activity | 6 | 0 | 0 | 0 |
| Eve\Models\Blog | blog | 161 | 0 | 6 | 0 |
| Eve\Models\Doc | doc | 0 | 3 | 0 | 0 |
| Eve\Models\Docpro | docpro | 0 | 69 | 0 | 0 |
| Eve\Models\Experience | experience | 23 | 0 | 0 | 0 |
| Eve\Models\Gallery | gallery | 6 | 0 | 0 | 0 |
| Eve\Models\Interview | interview | 9 | 0 | 3 | 0 |
| Eve\Models\Member | member | 0 | 2 | 0 | 0 |
| Eve\Models\Sponsor | sponsor | 5 | 0 | 0 | 0 |
| Eve\Models\Testimonial | testimonial | 10 | 0 | 0 | 0 |
| Eve\Models\Video | video | 0 | 0 | 2 | 0 |
| Lara\Common\Models\Larawidget | larawidget | 1 | 0 | 0 | 0 |
| Lara\Common\Models\Page | page | 21 | 0 | 0 | 0 |
| Lara\Common\Models\Slider | slider | 17 | 0 | 0 | 0 |

### Media files on disk (/Users/hoeksms/Code/__cloudways/__migrate/gain8246/.lara/production_switch/media)

| Folder | Content type | Rows | Only in _archive | Missing | Files in folder | Not referenced | Subfolders |
| --- | --- | --- | --- | --- | --- | --- | --- |
| _temp | — | 0 | 0 | 0 | 13 | 13 |  |
| activity | yes | 6 | 0 | 0 | 6 | 0 |  |
| blog | yes | 161 | 0 | 0 | 236 | 75 |  |
| contactform | — | 0 | 0 | 0 | 0 | 0 |  |
| cta | yes | 0 | 0 | 0 | 3 | 3 |  |
| doc | yes | 3 | 0 | 0 | 3 | 0 |  |
| docpro | yes | 69 | 0 | 0 | 69 | 0 |  |
| event | yes | 0 | 0 | 0 | 0 | 0 |  |
| experience | yes | 23 | 0 | 0 | 43 | 20 |  |
| faq | yes | 0 | 0 | 0 | 0 | 0 |  |
| files | — | 0 | 0 | 0 | 0 | 0 | shares |
| gallery | yes | 6 | 0 | 0 | 6 | 0 |  |
| images | — | 0 | 0 | 0 | 0 | 0 | shares |
| interview | yes | 9 | 0 | 0 | 23 | 14 |  |
| larawidget | yes | 1 | 0 | 0 | 2 | 1 |  |
| location | yes | 0 | 0 | 0 | 0 | 0 |  |
| member | — | 0 | 0 | 0 | 2 | 2 |  |
| organizer | yes | 0 | 0 | 0 | 0 | 0 |  |
| page | yes | 21 | 0 | 0 | 21 | 0 |  |
| portfolio | yes | 0 | 0 | 0 | 8 | 8 |  |
| product | — | 0 | 0 | 0 | 0 | 0 |  |
| redirect | — | 0 | 0 | 0 | 0 | 0 |  |
| slider | yes | 17 | 0 | 0 | 27 | 10 |  |
| sponsor | — | 0 | 0 | 0 | 8 | 8 |  |
| step | yes | 0 | 0 | 0 | 0 | 0 |  |
| tag | — | 0 | 0 | 0 | 0 | 0 |  |
| team | — | 0 | 0 | 0 | 13 | 13 |  |
| testimonial | yes | 10 | 0 | 0 | 11 | 1 |  |
| textwidget | — | 0 | 0 | 0 | 6 | 6 |  |
| video | yes | 0 | 0 | 0 | 1 | 1 |  |

### Other data

| What | Rows | Detail |
| --- | --- | --- |
| Users | 13 |  |
| Roles | 5 | frontend, leden, editor, webmaster, administrator |
| Abilities | 140 |  |
| Languages | 2 | nl, en |
| Menus | 2 |  |
| Menu items | 71 | entity 14, page 50, parent 6, root 1 |
| Redirects | 0 |  |
| Taxonomies | 2 | category, tag |
| Tags | 70 | blog 14, cta 1, doc 5, event 3, experience 4, gallery 2, member 6, page 1, portfolio 3, product 8, slider 8, team 13, video 2 |
| Taggables | 412 |  |
| SEO rows | 441 |  |
| Opengraph rows | 174 |  |
| Layout rows | 5 |  |
| Related rows | 578 |  |
| Pageables | 10 |  |
| Sync rows | 0 |  |
| Settings | 43 | company 21, general 2, opengraph 5, system 15 |
| Translations | 1797 | eve 2, lara-admin 1273, lara-common 21, lara-eve 386, lara-front 115 |
| Header tags | 62 |  |
| Blacklist | 0 |  |

### Findings (no v10 mapping, a change in behaviour, or inconsistent v8 data)

| # | Finding |
| --- | --- |
| 1 | Content type 'sponsor' is skipped: 6 record(s), 5 media row(s) and 0 menu item(s) are not imported. |
| 2 | Content type 'member' is skipped: 21 record(s), 2 media row(s) and 1 menu item(s) are not imported. |
| 3 | Site field settings (v8-fields.php): docpro.secretkey left out, interview.paragraph1 richeditor, interview.par1quote select, interview.par1quotepos select, interview.par1img select (v8 image id → v10 media id), interview.par1imgpos select, interview.paragraph2 richeditor, interview.par2quote select, interview.par2quotepos select, interview.par2img select (v8 image id → v10 media id), interview.par2imgpos select, interview.paragraph3 richeditor, interview.par3quote select, interview.par3quotepos select, interview.par3img select (v8 image id → v10 media id), interview.par3imgpos select, interview.paragraph4 richeditor, interview.par4quote select, interview.par4quotepos select, interview.par4img select (v8 image id → v10 media id), interview.par4imgpos select. |
| 4 | Site field settings (v8-fields.php): v8 column page.lead becomes the custom field 'intro' (richeditor). |
| 5 | cta.hook: 'homeparalax1' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 6 | cta.hook: 'homeparalax2' is only used by unpublished or trashed records (1x); not added as an option, the value is imported unchanged. |
| 7 | lara_content_pages.body: v8 column shortcodes ([kolom_XvanN]) in 1 row(s) (ids 1); import-content turns them into rich editor grids (the client theme needs the .grid-layout CSS). |

7 finding(s): see the last section.
