<?php

namespace App\Support;

/**
 * The single place that decides what to show when a user has no profile
 * photo. It draws one initial letter on a colour chosen from the user's id,
 * so the same account always gets the same colour everywhere
 * it's shown (header, Home, Explore, popups, ...). Was previously
 * reimplemented ad-hoc in ~30 different places with different initial
 * lengths (1 letter vs 2 chars vs 2-word initials) and different colour
 * rules (fixed per page, or two different hash functions on the admin
 * Users page that could disagree with each other for the same account).
 *
 * The JavaScript copy of this palette lives in public/js/avatar.js. Keep both
 * lists in the same order, because the colour is picked with id % length and
 * has to land on the same entry in both places for the same id.
 */
class Avatar
{
    public const PALETTE = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'];

    public static function initial(?string $name): string
    {
        $name = trim((string) $name);

        return $name === '' ? '?' : mb_strtoupper(mb_substr($name, 0, 1));
    }

    public static function color(int|string|null $seed): string
    {
        $n = (int) $seed;
        $palette = self::PALETTE;

        return $palette[abs($n) % count($palette)];
    }
}
