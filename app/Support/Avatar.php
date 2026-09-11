<?php

namespace App\Support;

/**
 * Single source of truth for the "no profile photo" avatar fallback —
 * one initial letter on a colour picked deterministically from the
 * user's id, so the same account always gets the same colour everywhere
 * it's shown (header, Home, Explore, popups, ...). Was previously
 * reimplemented ad-hoc in ~30 different places with different initial
 * lengths (1 letter vs 2 chars vs 2-word initials) and different colour
 * rules (fixed per page, or two different hash functions on the admin
 * Users page that could disagree with each other for the same account).
 *
 * The JS twin of this palette lives in public/js/avatar.js — keep them
 * in the same order, since colour(id) is a plain id % length lookup and
 * has to land on the same colour in both places for the same id.
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
