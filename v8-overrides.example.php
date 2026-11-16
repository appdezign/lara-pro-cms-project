<?php

/*
 * The site's deliberate differences between v10 and the v8 data (SiteOverrides).
 *
 * Copy to v8-overrides.php (or point LARA_V8_OVERRIDES to the file). Every lara:v8:* import
 * applies them while it builds the rows, so a rerun keeps them and lara:v8:verify sees them as
 * in sync. Each entry: the v10 table, the row to match (`where`, all columns equal), the values
 * to `set` and/or the text to `replace` inside a column, and why. Change v10 content you want to
 * keep through the final sync here, not only in the admin.
 */

return [
    // ['table' => 'lara_blocks_widgets', 'where' => ['id' => 12], 'set' => ['hook' => 'home2'], 'reason' => 'Second on the home page'],
    // ['table' => 'lara_content_blogs', 'where' => ['id' => 40], 'replace' => ['body' => ['/nl/old-page' => '/nl/new-page']], 'reason' => 'Old link'],
    // ['table' => 'lara_sys_languages', 'where' => ['code' => 'en'], 'set' => ['publish' => 0], 'reason' => 'English not in use'],
];
