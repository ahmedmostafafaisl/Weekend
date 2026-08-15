<?php

namespace App\Support\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache-key helper: portable "tag-like" invalidation that works
 * on ANY cache driver, including this project's own default (file — which
 * does not support Cache::tags() at all; only redis/memcached do).
 *
 * How it works: every cache entry's actual key embeds a per-group version
 * number. Reading always fetches the current version and builds the key
 * from it; writing simply increments that version. Since the version
 * number changed, every previously-built key becomes unreachable — the
 * old cache entries still physically exist until their own TTL expires,
 * but nothing will ever look them up again, which is functionally
 * equivalent to invalidating them immediately.
 *
 * Combined with a TTL on the actual data entry (typically 24 hours here),
 * this gives exactly the "changes every 24 hours OR on new data" behavior
 * these endpoints were asked for: the TTL invalidates on its own schedule,
 * and bumpCacheVersion() invalidates immediately on a write, whichever
 * happens first.
 *
 * VERIFIED (not assumed) against Laravel's real FileStore source before
 * relying on this: incrementing a version key that has never been set
 * before correctly defaults to a "store forever" TTL on the file driver
 * (FileStore::expiration() special-cases a $seconds value of 0 to mean
 * never-expire, and FileStore::increment() passes exactly 0 as the TTL
 * for a payload it read as empty/missing) — so no separate initialization
 * step is needed before the first increment.
 */
trait HasVersionedCache
{
    /**
     * Build the current, live cache key for $group (and any further
     * distinguishing parts, e.g. a user id or filter values) — includes
     * the group's current version number, so a stale key from before the
     * last bumpCacheVersion() call is never reused.
     */
    protected function versionedCacheKey(string $group, ...$parts): string
    {
        $version = Cache::get($this->versionCacheKeyName($group), 0);

        return implode(':', array_merge([$group, "v{$version}"], array_map('strval', $parts)));
    }

    /**
     * Invalidate every cache entry for $group immediately, regardless of
     * their individual TTLs — call this after any write (create/update/
     * delete) that changes data this group's cached responses depend on.
     */
    protected function bumpCacheVersion(string $group): void
    {
        Cache::increment($this->versionCacheKeyName($group));
    }

    private function versionCacheKeyName(string $group): string
    {
        return "{$group}:__version";
    }
}
