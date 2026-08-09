<?php

declare(strict_types=1);

use Usamamuneerchaudhary\FilaRank\Models\SeoMeta;

return [

    /*
    |--------------------------------------------------------------------------
    | Site defaults
    |--------------------------------------------------------------------------
    | Used as fallbacks by the <x-filarank::tags /> component when a model
    | has no SEO meta of its own.
    */
    'site' => [
        'name' => env('APP_NAME', 'Laravel'),
        'title_separator' => ' — ',
        'description' => null,
        'og_image' => null,
        'twitter_handle' => null, // e.g. '@yourbrand'
    ],

    /*
    |--------------------------------------------------------------------------
    | Tag rendering
    |--------------------------------------------------------------------------
    */
    'render' => [
        'title_tag' => true,    // Render the <title> element itself.
        'canonical' => true,
        'open_graph' => true,
        'twitter_cards' => true,
        'json_ld' => true,
        'json_ld_type' => 'Article', // Article | Page | Post …
    ],

    /*
    |--------------------------------------------------------------------------
    | Analysis
    |--------------------------------------------------------------------------
    | Disable individual checks by id, e.g. ['outbound-links'].
    | Set "site_host" so internal/outbound link detection can classify
    | absolute URLs pointing at your own domain (defaults to APP_URL's host).
    */
    'analysis' => [
        'disabled_checks' => [],
        'site_host' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistence
    |--------------------------------------------------------------------------
    | When true, the overall score is recalculated and stored on the
    | seo_metas row every time the parent model is saved, so it can be
    | shown in table columns without re-analysing.
    */
    'persist_score' => true,

    /*
    |--------------------------------------------------------------------------
    | Cross-record reports (Pro)
    |--------------------------------------------------------------------------
    | Models to include in the cannibalization scan and internal-linking
    | reports. Each must use the HasSeo trait. Example:
    |   \App\Models\Post::class => ['label' => 'title'],
    */
    'scanned_models' => [
        // \App\Models\Post::class => ['label' => 'title'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects (Pro)
    |--------------------------------------------------------------------------
    */
    'redirects' => [
        'enabled' => true,
        // Paths (supports * wildcard suffix) never logged as 404s.
        'ignore_paths' => ['/telescope*', '/horizon*', '/_debugbar*', '/livewire*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cornerstone (Pro)
    |--------------------------------------------------------------------------
    | Minimum incoming internal links before a cornerstone stops being
    | flagged as under-linked.
    */
    'cornerstone' => [
        'min_incoming_links' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    */
    'seo_meta_model' => SeoMeta::class,
    'table_name' => 'lara_object_seo_metas',
];
