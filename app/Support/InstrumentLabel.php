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

        $normalized = strtolower(trim($code));

        if (isset(static::$cache[$normalized])) {
            return static::$cache[$normalized];
        }

        if ($category && !empty($category->display_name)) {
            return static::$cache[$normalized] = trim($category->display_name);
        }

        // Check single source of truth catalog first (0 DB queries for all standard instruments)
        $catalogName = InstrumentCatalog::get($normalized);
        if (!empty($catalogName)) {
            return static::$cache[$normalized] = $catalogName;
        }

        // If category instance is already loaded in memory and has no display_name, fallback directly without querying DB
        if ($category !== null) {
            return static::$cache[$normalized] = static::humanize($code);
        }

        // Check if a category model with non-null display_name exists in database
        try {
            $dbDisplayName = QuestionCategory::whereRaw('LOWER(name) = ?', [$normalized])
                ->whereNotNull('display_name')
                ->where('display_name', '!=', '')
                ->value('display_name');

            if (!empty($dbDisplayName)) {
                return static::$cache[$normalized] = trim($dbDisplayName);
            }
        } catch (\Throwable $e) {
            // During migrations or when DB table not yet migrated, fall back gracefully
        }

        // Clean humanized fallback
        return static::$cache[$normalized] = static::humanize($code);
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
    }
}
