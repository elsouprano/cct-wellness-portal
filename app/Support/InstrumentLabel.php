<?php

namespace App\Support;

use App\Models\QuestionCategory;
use Illuminate\Support\Str;

class InstrumentLabel
{
    /**
     * Request-level in-memory cache for resolved instrument labels.
     *
     * @var array<string, string>
     */
    protected static array $cache = [];

    /**
     * Request-level bulk-loaded DB map of lowercase category name => display_name.
     *
     * @var array<string, string>|null
     */
    protected static ?array $dbDisplayNames = null;

    /**
     * Resolve the full official display label for an assessment category.
     *
     * Order of resolution:
     * 1. If a QuestionCategory instance is provided and has a display_name, use it.
     * 2. Request cache.
     * 3. Database lookup for question_categories.display_name matching the code.
     * 4. App\Support\InstrumentCatalog mapping.
     * 5. Humanized fallback (acronym-like codes upper-cased, multi-word Title Cased).
     *
     * @param string|null $code
     * @param QuestionCategory|null $category
     * @return string
     */
    public static function for(?string $code, ?QuestionCategory $category = null): string
    {
        if (empty($code)) {
            return '';
        }

        // 1. Always honor an explicit instance display_name first without poisoning global code cache
        if ($category && !empty($category->display_name)) {
            return trim($category->display_name);
        }

        $normalized = strtolower(trim($code));

        // 2. Request-level cache
        if (isset(static::$cache[$normalized])) {
            return static::$cache[$normalized];
        }

        // 3. Database display_name override (bulk-loaded once per request to avoid N+1)
        if ($category === null) {
            $dbDisplayName = static::lookupDbDisplayName($normalized);
            if ($dbDisplayName !== null) {
                return static::$cache[$normalized] = $dbDisplayName;
            }
        }

        // 4. Single source of truth catalog
        $catalogName = InstrumentCatalog::get($normalized);
        if (!empty($catalogName)) {
            return static::$cache[$normalized] = $catalogName;
        }

        // 5. Clean humanized fallback
        return static::$cache[$normalized] = static::humanize($code);
    }

    protected static function lookupDbDisplayName(string $normalized): ?string
    {
        if (static::$dbDisplayNames === null) {
            static::$dbDisplayNames = [];
            try {
                $rows = QuestionCategory::whereNotNull('display_name')
                    ->where('display_name', '!=', '')
                    ->orderByDesc('id')
                    ->get(['name', 'display_name']);

                foreach ($rows as $row) {
                    $key = strtolower(trim((string) $row->name));
                    if ($key !== '' && !isset(static::$dbDisplayNames[$key])) {
                        static::$dbDisplayNames[$key] = trim((string) $row->display_name);
                    }
                }
            } catch (\Throwable $e) {
                // During migrations or when DB table not yet migrated, fall back gracefully
                static::$dbDisplayNames = null;
                return null;
            }
        }

        return static::$dbDisplayNames[$normalized] ?? null;
    }

    /**
     * Fallback formatter: acronym-like codes upper-cased, others Title Cased.
     * e.g., 'learning_style' -> 'Learning Style', 'caq' -> 'CAQ', 'irq' -> 'IRQ'.
     */
    public static function humanize(string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            return '';
        }

        if (str_contains($code, '_') || str_contains($code, '-')) {
            return Str::title(str_replace(['_', '-'], ' ', $code));
        }

        // Short words (<= 5 chars) or codes containing numbers are treated as acronyms
        if (strlen($code) <= 5 || preg_match('/\d/', $code)) {
            return strtoupper($code);
        }

        return Str::title($code);
    }

    /**
     * Clear the request cache (useful in tests and updates).
     */
    public static function clearCache(): void
    {
        static::$cache = [];
        static::$dbDisplayNames = null;
    }
}
