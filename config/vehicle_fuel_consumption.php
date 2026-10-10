<?php

/**
 * Real-world Malaysian fuel consumption per vehicle model, compiled from
 * live web research on 2026-08-23. These replace the AI guessed km/L for the
 * models
 * listed here. A vehicle not matched by any pattern below still falls
 * through to the AI estimate / keyword-based emergency fallback exactly as
 * before, and nothing here is invented.
 *
 * city_kmpl / highway_kmpl are the midpoint of owner-reported real-world
 * ranges reported by owners, not the manufacturer's brochure figure. Those
 * official numbers run 30 to 50 percent better than real Malaysian mixed
 * driving and would make the fuel cost look too low.
 *
 * 'combined_only' => true means the source only published one mixed figure,
 * so the same number is used for city and highway. Those entries are less
 * reliable than the ones with separate values.
 *
 * 'match' is checked first (exact, fast). 'fuzzy_tokens', if present, is a
 * safety net for typos and sloppy typing. Every word listed has to find a
 * close match, within about one edit, among the words the driver typed.
 * Left empty/absent on the 1.5L variant on purpose: a mistyped displacement
 * number is too easy to confuse with a different real variant, so only
 * exact typing selects it. A typo falls through to the generic model entry
 * below instead, which is still a reasonable approximation.
 *
 * Patterns are checked in order, most-specific variant first.
 */
return [

    'perodua_myvi_1.5' => [
        'label' => 'Perodua Myvi 1.5L', 'match' => '/myvi.*1\.5|1\.5.*myvi/i', 'fuzzy_tokens' => [],
        'fuel_type' => 'RON95', 'city_kmpl' => 13.0, 'highway_kmpl' => 18.0, 'combined_only' => false,
        'source' => 'https://stereng.com/fuel-consumption/myvi/', 'verified_at' => '2026-08-23',
    ],
    'perodua_myvi' => [
        'label' => 'Perodua Myvi', 'match' => '/\bmyvi\b/i', 'fuzzy_tokens' => ['myvi'],
        'fuel_type' => 'RON95', 'city_kmpl' => 14.0, 'highway_kmpl' => 19.0, 'combined_only' => false,
        'source' => 'https://stereng.com/fuel-consumption/myvi/', 'verified_at' => '2026-08-23',
    ],
    'perodua_axia' => [
        'label' => 'Perodua Axia', 'match' => '/\baxia\b/i', 'fuzzy_tokens' => ['axia'],
        'fuel_type' => 'RON95', 'city_kmpl' => 15.5, 'highway_kmpl' => 18.5, 'combined_only' => false,
        'source' => 'https://stereng.com/fuel-consumption/axia/', 'verified_at' => '2026-08-23',
    ],
    'perodua_bezza' => [
        'label' => 'Perodua Bezza', 'match' => '/\bbezza\b/i', 'fuzzy_tokens' => ['bezza'],
        'fuel_type' => 'RON95', 'city_kmpl' => 15.5, 'highway_kmpl' => 18.5, 'combined_only' => false,
        'source' => 'https://stereng.com/fuel-consumption/bezza/', 'verified_at' => '2026-08-23',
    ],
    'proton_saga' => [
        'label' => 'Proton Saga', 'match' => '/\bsaga\b/i', 'fuzzy_tokens' => ['saga'],
        'fuel_type' => 'RON95', 'city_kmpl' => 14.0, 'highway_kmpl' => 17.5, 'combined_only' => false,
        'source' => 'https://stereng.com/fuel-consumption/saga/', 'verified_at' => '2026-08-23',
    ],
    'honda_city' => [
        'label' => 'Honda City', 'match' => '/honda.*\bcity\b|\bcity\b.*honda/i', 'fuzzy_tokens' => ['honda', 'city'],
        'fuel_type' => 'RON95', 'city_kmpl' => 13.9, 'highway_kmpl' => 13.9, 'combined_only' => true,
        'source' => 'https://www.wapcar.my/news/21430honda-city-vs-nissan-almera-vs-toyota-vios-which-bsegment-in-malaysia-offers-the-best-fuel-economy-21430',
        'verified_at' => '2026-08-23',
    ],
    'toyota_vios' => [
        'label' => 'Toyota Vios', 'match' => '/\bvios\b/i', 'fuzzy_tokens' => ['vios'],
        'fuel_type' => 'RON95', 'city_kmpl' => 14.7, 'highway_kmpl' => 14.7, 'combined_only' => true,
        'source' => 'https://www.wapcar.my/news/21430honda-city-vs-nissan-almera-vs-toyota-vios-which-bsegment-in-malaysia-offers-the-best-fuel-economy-21430',
        'verified_at' => '2026-08-23',
    ],
    'toyota_hilux' => [
        'label' => 'Toyota Hilux', 'match' => '/\bhilux\b/i', 'fuzzy_tokens' => ['hilux'],
        'fuel_type' => 'Diesel', 'city_kmpl' => 11.1, 'highway_kmpl' => 11.1, 'combined_only' => true,
        'source' => 'https://www.pcauto.com/my/cars/toyota/hilux/fuel-consumption', 'verified_at' => '2026-08-23',
    ],
    'ford_ranger' => [
        'label' => 'Ford Ranger', 'match' => '/\branger\b/i', 'fuzzy_tokens' => ['ranger'],
        'fuel_type' => 'Diesel', 'city_kmpl' => 10.6, 'highway_kmpl' => 10.6, 'combined_only' => true,
        'source' => 'https://www.wapcar.my/cars/ford/ranger/fuel-consumption', 'verified_at' => '2026-08-23',
    ],
];
