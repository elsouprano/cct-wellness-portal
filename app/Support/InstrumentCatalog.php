<?php

namespace App\Support;

class InstrumentCatalog
{
    /**
     * Single source of truth for verified assessment instrument official names.
     * Every confirmed standardized instrument includes its official acronym in parentheses.
     *
     * Unconfirmed codes (such as 'caq' and 'irq') are intentionally omitted so they
     * fallback to uppercase codes and are listed in the research adviser report.
     *
     * @var array<string, string>
     */
    protected static array $catalog = [
        'rapid_headsss' => 'Rapid HEADSSS Assessment (RAPID-HEADSSS)',
        'dass21' => 'Depression Anxiety Stress Scales (DASS-21)',
        'sdq' => 'Strengths and Difficulties Questionnaire (SDQ)',
        'sfs' => 'Social Functioning Scale (SFS)',
        'sfq' => 'Social Functioning Questionnaire (SFQ)',
        'audit' => 'Alcohol Use Disorders Identification Test (AUDIT)',
        'btq' => 'Brief Trauma Questionnaire (BTQ)',
        'bpaq' => 'Buss-Perry Aggression Questionnaire (BPAQ)',
        'ffmq' => 'Five Facet Mindfulness Questionnaire (FFMQ)',
        'ars30' => 'Academic Resilience Scale (ARS-30)',
        'erq' => 'Emotion Regulation Questionnaire (ERQ)',
        'irq' => 'Interpersonal Regulation Questionnaire (IRQ)',
        'cat' => 'College Adjustment Test (CAT)',
        'eat' => 'Eating Assessment Tool (EAT-10)',
        'big_five' => 'Big Five Personality Inventory (Big Five)',
        'work_values' => 'Work Values Inventory (Work Values)',
        'career_interest' => 'Career Interest Assessment',
        'career_readiness' => 'Career Readiness Survey',
        'skills_inventory' => 'Skills Inventory Assessment',
        'learning_style' => 'Learning Style Inventory',
        'profile' => 'Student Profile & Demographics',
    ];

    /**
     * Retrieve the confirmed official instrument name by code.
     */
    public static function get(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $normalized = strtolower(trim($code));

        return static::$catalog[$normalized] ?? null;
    }

    /**
     * Return all confirmed catalog entries.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return static::$catalog;
    }

    /**
     * Check if a code has a confirmed official catalog entry.
     */
    public static function has(?string $code): bool
    {
        if ($code === null) {
            return false;
        }

        return isset(static::$catalog[strtolower(trim($code))]);
    }
}

