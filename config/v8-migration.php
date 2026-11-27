<?php

/*
 * Settings for the Lara CMS v8 → v10 migration (lara:v8:* commands).
 *
 * The source database is the "lara_v8" connection in config/database.php.
 * See docs/migrate-v8/RUNBOOK.md for the procedure.
 */

return [

    /*
     * Database connection of the v8 source (read-only).
     */
    'connection' => 'lara_v8',

    /*
     * Absolute path of the v8 media folder (the v8 "localdisk" root, public/assets/media),
     * with one subfolder per entity key. Files are only read from here.
     */
    'media_path' => env('LARA_V8_MEDIA_PATH'),

    /*
     * The host names of the v8 site (comma separated in LARA_V8_SITE_HOSTS, e.g. "anpak.eu"),
     * so absolute URLs to the site itself are treated as internal (www. is ignored).
     */
    /*
     * The site's deliberate differences from v8, applied by every import (SiteOverrides):
     * a PHP file returning a list of ['table', 'where', 'set', 'reason']. Site data: keep it
     * out of the migration branch. See v8-overrides.example.php.
     */
    'overrides' => env('LARA_V8_OVERRIDES', base_path('v8-overrides.php')),

    /*
     * The site's settings for single v8 custom fields (SiteFields): a v10 field type (and
     * options) instead of the mapped one, a field left out, or a field whose value is the id
     * of one of the record's own v8 images. A PHP file returning ['entity.field' => [...]].
     * Site data: keep it out of the migration branch. See v8-fields.example.php.
     */
    'fields' => env('LARA_V8_FIELDS', base_path('v8-fields.php')),

    /*
     * v8 content types the site leaves out (comma separated entity keys in LARA_V8_SKIP_ENTITIES,
     * e.g. "city,product"), such as demo leftovers: not built, their data not imported, and
     * what is dropped is reported. Core content types and the standard ones (blog, doc,
     * gallery) cannot be skipped.
     */
    'skip_entities' => array_values(array_filter(array_map('trim', explode(',', (string) env('LARA_V8_SKIP_ENTITIES', ''))))),

    'site_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('LARA_V8_SITE_HOSTS', ''))))),

];
