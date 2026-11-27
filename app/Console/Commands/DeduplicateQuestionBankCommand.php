<?php

namespace App\Console\Commands;

use App\Services\QuestionBankDeduplicationService;
use Illuminate\Console\Command;

class DeduplicateQuestionBankCommand extends Command
{
    protected $signature = 'question-bank:deduplicate';

    protected $description = 'Back up Question Bank tables to storage/app/backups/ and merge duplicate categories and unreferenced duplicate questions in one transaction.';

    public function handle(QuestionBankDeduplicationService $service): int
    {
        $this->info('Starting Question Bank backup and deduplication...');

        $summary = $service->deduplicate();

        $this->info('Backup saved to: ' . $summary['backup_path']);
        $this->table(
            ['Metric', 'Count'],
            [
                ['Category groups merged', $summary['categories_merged']],
                ['Redundant category rows removed', $summary['redundant_categories_deleted']],
                ['Unreferenced duplicate questions removed', $summary['duplicate_items_deleted']],
                ['Duplicate questions preserved (has responses)', $summary['duplicate_items_preserved_with_responses']],
                ['BTQ follow-up prompts disambiguated', $summary['btq_followups_disambiguated']],
            ]
        );

        return self::SUCCESS;
    }
}
