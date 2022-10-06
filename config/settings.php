<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local site domain
    |--------------------------------------------------------------------------
    |
    | Used by the `scraper` console command to parse URLs.
    |
    */

    'sitemap' => [
      'url' => env('SITEMAP_URL')
    ],
    'production' => [
      'domain' => env('PRODUCTION_DOMAIN')
    ],
    'local' => [
      'domain' => env('LOCAL_DOMAIN')
    ],
    'convert-sitemap-urls-to-local' => env('CONVERT_SITEMAP_TO_LOCAL_URLS', false),

];
