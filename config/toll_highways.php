<?php

/**
 * Malaysian toll highway rates for Class 1 (cars), compiled from live web
 * research on 2026-08-23 — replaces the old AI-guessed / hardcoded-keyword
 * toll estimate with sourced real rates. Anything not listed here (ELITE,
 * SKVE, WCE, and any road not matched below) still falls through to the AI
 * estimate — nothing is invented to fill those gaps.
 *
 * 'match' patterns are checked against the route's road-name text (from
 * OSRM/Nominatim), most-specific first. 'type' => 'flat' pays the rate once
 * per matched highway; 'per_km' multiplies distance_km by rate_per_km
 * (capped) — used for the closed-toll long-haul networks where a single
 * flat number doesn't fit a trip of arbitrary length.
 *
 * Where sources disagreed, the rate actually fetched from a direct page
 * read (not a search-summary snippet) was kept; see the Fare Advisor
 * Sourcebook for the conflicting alternates.
 */
return [

    'plus_nse' => [
        'label'       => 'PLUS/NSE',
        'match'       => '/\bplus\b|lebuhraya utara[\s\-\x{2013}\x{2014}]selatan|north[\s\-\x{2013}\x{2014}]south expressway|\bnse\b/iu',
        'type'        => 'per_km',
        'rate_per_km' => 0.11,
        'max_cost'    => 60.0,
        'min_km'      => 15.0,
        'source'      => 'https://calculatormalaysia.com/auto/plus-highway-toll-rates-malaysia/',
        'verified_at' => '2026-08-23',
    ],
    'lpt2' => [
        'label'       => 'LPT2',
        'match'       => '/\blpt\s*2\b|lebuhraya pantai timur\s*2|pantai timur\s*2/i',
        'type'        => 'flat',
        'rate'        => 19.70,
        'source'      => 'https://tollguru.com/malaysia-toll',
        'verified_at' => '2026-08-23',
    ],
    'lpt1' => [
        'label'       => 'LPT1',
        'match'       => '/\blpt\s*1\b|lebuhraya pantai timur(?!\s*2)|pantai timur(?!\s*2)/i',
        'type'        => 'flat',
        'rate'        => 20.30,
        'source'      => 'https://tollguru.com/malaysia-toll',
        'verified_at' => '2026-08-23',
    ],
    'lekas' => [
        'label'       => 'LEKAS',
        'match'       => '/\blekas\b|kajang[\s\-\x{2013}\x{2014}](seremban|setul)/iu',
        'type'        => 'flat',
        'rate'        => 7.80,
        'source'      => 'https://tollguru.com/malaysia-toll',
        'verified_at' => '2026-08-23',
    ],

    'duke' => [
        'label' => 'DUKE', 'match' => '/\bduke\b/i', 'type' => 'flat', 'rate' => 2.50,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'ldp' => [
        'label' => 'LDP', 'match' => '/\bldp\b|damansara[\s\-\x{2013}\x{2014}]puchong/iu', 'type' => 'flat', 'rate' => 2.10,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'kesas' => [
        'label' => 'KESAS', 'match' => '/\bkesas\b|shah alam expressway|lebuhraya shah alam\b/i', 'type' => 'flat', 'rate' => 2.00,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'mex' => [
        'label' => 'MEX', 'match' => '/\bmex\b|maju expressway|kuala lumpur[\s\-\x{2013}\x{2014}]putrajaya/iu', 'type' => 'flat', 'rate' => 2.50,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'sprint' => [
        'label' => 'SPRINT', 'match' => '/\bsprint\b/i', 'type' => 'flat', 'rate' => 2.50,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'akleh' => [
        'label' => 'AKLEH', 'match' => '/\bakleh\b|ampang[\s\-\x{2013}\x{2014}]+kuala lumpur/iu', 'type' => 'flat', 'rate' => 2.13,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'npe' => [
        'label' => 'NPE', 'match' => '/\bnpe\b|new pantai expressway/i', 'type' => 'flat', 'rate' => 2.30,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'besraya' => [
        'label' => 'BESRAYA', 'match' => '/\bbesraya\b|lebuhraya sungai besi\b/i', 'type' => 'flat', 'rate' => 1.85,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'silk' => [
        'label' => 'SILK', 'match' => '/\bsilk\b/i', 'type' => 'flat', 'rate' => 1.66,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'gce' => [
        'label' => 'GCE', 'match' => '/\bgce\b|guthrie/i', 'type' => 'flat', 'rate' => 1.75,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'smart' => [
        'label' => 'SMART', 'match' => '/smart\s*tunnel/i', 'type' => 'flat', 'rate' => 3.00,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'suke' => [
        'label' => 'SUKE', 'match' => '/\bsuke\b/i', 'type' => 'flat', 'rate' => 2.30,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'dash' => [
        'label' => 'DASH', 'match' => '/\bdash\b|damansara[\s\-\x{2013}\x{2014}]shah alam/iu', 'type' => 'flat', 'rate' => 2.30,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'grandsaga' => [
        'label' => 'GRANDSAGA', 'match' => '/grand\s*saga|cheras[\s\-\x{2013}\x{2014}]kajang/iu', 'type' => 'flat', 'rate' => 1.30,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'latar' => [
        'label' => 'LATAR', 'match' => '/\blatar\b|kuala lumpur[\s\-\x{2013}\x{2014}]kuala selangor/iu', 'type' => 'flat', 'rate' => 1.90,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'lksa' => [
        'label' => 'LKSA', 'match' => '/\blksa\b/i', 'type' => 'flat', 'rate' => 1.20,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    // "SEPADU" is the name of an integrated toll-payment ZONE covering several
    // West Port-area plazas (Bukit Raja/Kapar/MOC), not one road's own name —
    // OSM tags the underlying road by its real name, which isn't researched
    // yet, so this pattern realistically won't ever match. Left in rather
    // than deleted so a future pass has the rate ready once a real name is found.
    'sepadu' => [
        'label' => 'SEPADU', 'match' => '/\bsepadu\b/i', 'type' => 'flat', 'rate' => 1.55,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'spdh' => [
        'label' => 'SPDH', 'match' => '/\bspdh\b|seremban[\s\-\x{2013}\x{2014}]port dickson/iu', 'type' => 'flat', 'rate' => 1.65,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
    'klk' => [
        'label' => 'KLK', 'match' => '/\bklk\b|kuala lumpur[\s\-\x{2013}\x{2014}]karak/iu', 'type' => 'flat', 'rate' => 4.75,
        'source' => 'https://www.motorist.my/ms/article/2829/list-of-malaysia-highway-toll-rates-plus-klk-lpt-and-others-2026-edition',
        'verified_at' => '2026-08-23',
    ],
];
