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

];
