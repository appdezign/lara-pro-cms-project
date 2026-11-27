<?php

/*
 * The site's settings for single v8 custom fields (SiteFields).
 *
 * Copy to v8-fields.php (or point LARA_V8_FIELDS to the file). Keys are "{entity key}.{field
 * name}" of a site content type; lara:v8:import-entities builds the field with these settings
 * and lara:v8:import-content / import-media fill it. Each entry:
 *
 * - 'type' (and 'options'): the v10 field type (Lara\Admin\Enums\CustomFieldType) instead of
 *   the mapped one, e.g. for a v8 'custom' field, which otherwise becomes a textarea.
 * - 'skip' => true: the field is not built and its values are not imported.
 * - 'image' => true: the value is the id of one of the record's own v8 images
 *   (lara_object_images); it becomes the v10 media id (curator) of that image. import-media
 *   fills it once the images exist.
 * - 'reason': why.
 */

return [
    // 'docpro.secretkey' => ['skip' => true, 'reason' => 'Share key; the reports are public now'],
    // 'interview.paragraph1' => ['type' => 'richeditor', 'reason' => 'v8 TinyMCE field'],
    // 'interview.par1quotepos' => ['type' => 'select', 'options' => ['left', 'right', 'top', 'bottom'], 'reason' => 'v8 position select'],
    // 'interview.par1img' => ['type' => 'select', 'options' => ['get_record_images'], 'image' => true, 'reason' => 'One of the record\'s own images'],
];
