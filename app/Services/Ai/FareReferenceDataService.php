<?php

namespace App\Services\Ai;

/**
 * Looks up real, sourced Malaysian toll rates and vehicle fuel consumption
 * (config/toll_highways.php and config/vehicle_fuel_consumption.php)
 * instead of letting the AI guess them. A miss on either lookup is not an
 * error — callers fall through to the AI estimate / heuristic fallback for
 * whatever this service doesn't recognise.
 */
class FareReferenceDataService
{
    /**
     * @return array{label:string,fuel_type:string,city_kmpl:float,highway_kmpl:float,combined_only:bool,source:string,verified_at:string}|null
     */
    public function lookupVehicleConsumption(string $vehicle): ?array
    {
        $vehicle = trim($vehicle);
        if ($vehicle === '') {
            return null;
        }

        $entries = config('vehicle_fuel_consumption', []);

        // Exact match first — fast, precise, unchanged behaviour for anyone
        // who typed their model name correctly.
        foreach ($entries as $entry) {
            if (preg_match($entry['match'], $vehicle) === 1) {
                return $entry;
            }
        }

        // Typo safety net: only reached when nothing matched exactly. A
        // driver typing "mivi" or "hilx" shouldn't silently lose the real
        // sourced data just because of a slip — every required token must
        // still find a near-match (within ~1 edit) among the words typed.
        $inputWords = preg_split('/[^a-z0-9]+/i', strtolower($vehicle), -1, PREG_SPLIT_NO_EMPTY);
        if ($inputWords === []) {
            return null;
        }

        foreach ($entries as $entry) {
            $requiredTokens = $entry['fuzzy_tokens'] ?? [];
            if ($requiredTokens === []) {
                continue;
            }

            $allTokensHit = true;
            foreach ($requiredTokens as $token) {
                if (! $this->hasFuzzyHit($token, $inputWords)) {
                    $allTokensHit = false;
                    break;
                }
            }

            if ($allTokensHit) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @param  string[]  $inputWords
     */
    private function hasFuzzyHit(string $token, array $inputWords): bool
    {
        $maxDistance = max(1, (int) floor(strlen($token) * 0.25));

        foreach ($inputWords as $word) {
            // Cheap length guard before the distance calculation, and stops a
            // one-letter word like "a" from "fuzzy-hitting" a 4-letter token.
            if (abs(strlen($word) - strlen($token)) > $maxDistance) {
                continue;
            }

            if ($this->editDistance($word, $token) <= $maxDistance) {
                return true;
            }
        }

        return false;
    }

    /**
     * Optimal-string-alignment distance — Levenshtein plus a transposition
     * step. PHP's built-in levenshtein() charges 2 substitutions for a
     * swapped pair of adjacent letters (e.g. "axai" vs "axia"), which is by
     * far the most common real typo and would otherwise need a looser, more
     * false-positive-prone threshold just to catch it.
     */
    private function editDistance(string $a, string $b): int
    {
        $la = strlen($a);
        $lb = strlen($b);
        $d = [];

        for ($i = 0; $i <= $la; $i++) {
            $d[$i][0] = $i;
        }
        for ($j = 0; $j <= $lb; $j++) {
            $d[0][$j] = $j;
        }

        for ($i = 1; $i <= $la; $i++) {
            for ($j = 1; $j <= $lb; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $d[$i][$j] = min(
                    $d[$i - 1][$j] + 1,
                    $d[$i][$j - 1] + 1,
                    $d[$i - 1][$j - 1] + $cost,
                );

                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $d[$i][$j] = min($d[$i][$j], $d[$i - 2][$j - 2] + 1);
                }
            }
        }

        return $d[$la][$lb];
    }

    /**
     * Picks city vs highway km/L for a matched vehicle using average trip
     * speed as the road-type signal — the same distance/duration data the
     * fare form already has, so no extra request is needed to decide.
     */
    public function pickKmPerLiter(array $vehicleMatch, float $distanceKm, int $durationMin): float
    {
        if ($vehicleMatch['combined_only']) {
            return $vehicleMatch['city_kmpl'];
        }

        $speedKmh = $durationMin > 0 ? $distanceKm / ($durationMin / 60) : 0;

        return $speedKmh > 40 ? $vehicleMatch['highway_kmpl'] : $vehicleMatch['city_kmpl'];
    }

    /**
     * Scans road-name text for known Malaysian toll highways. Matching is
     * deliberately narrow (highway abbreviations/full names, not a generic
     * "lebuhraya" catch-all) — a broad match would wrongly flag free trunk
     * roads like the Federal Highway just for containing that word.
     *
     * @return array{matched:bool,toll_roads:string[],estimated_toll_cost:float,has_toll:bool}
     */
    public function detectTolls(string $roads, float $distanceKm): array
    {
        $roads = trim($roads);
        $tollRoads = [];
        $total = 0.0;

        if ($roads !== '') {
            foreach (config('toll_highways', []) as $highway) {
                if (preg_match($highway['match'], $roads) !== 1) {
                    continue;
                }

                $tollRoads[] = $highway['label'];

                if ($highway['type'] === 'per_km') {
                    if ($distanceKm >= $highway['min_km']) {
                        $total += min($highway['max_cost'], round($distanceKm * $highway['rate_per_km'], 2));
                    } else {
                        array_pop($tollRoads); // too short to plausibly be this closed-toll network
                    }
                } else {
                    $total += $highway['rate'];
                }
            }
        }

        return [
            'matched' => $tollRoads !== [],
            'toll_roads' => $tollRoads,
            'estimated_toll_cost' => round($total, 2),
            'has_toll' => $tollRoads !== [],
        ];
    }
}
