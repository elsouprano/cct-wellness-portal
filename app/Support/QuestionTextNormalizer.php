<?php

namespace App\Support;

class QuestionTextNormalizer
{
    /**
     * Normalize question text for duplicate comparison:
     * lowercase, trim, strip punctuation, and collapse whitespace.
     */
    public static function normalize(?string $text): string
    {
        $normalized = mb_strtolower(trim((string) $text), 'UTF-8');
        $normalized = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * Normalize a category code / key for uniqueness comparison.
     */
    public static function normalizeCode(?string $code): string
    {
        return mb_strtolower(trim((string) $code), 'UTF-8');
    }
}
