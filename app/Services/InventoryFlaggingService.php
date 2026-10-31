<?php

namespace App\Services;

use App\Models\InventorySubmission;

class InventoryFlaggingService
{
    protected $assessmentService;

    public function __construct(?ResponseQualityAssessmentService $assessmentService = null)
    {
        $this->assessmentService = $assessmentService ?? app(ResponseQualityAssessmentService::class);
    }

    /**
     * Analyze a student submission using the deterministic Response Quality Assessment engine.
     *
     * @param InventorySubmission $submission
     * @return \App\Models\ResponseQualityAssessment|null
     */
    public function analyze(InventorySubmission $submission)
    {
        return $this->assessmentService->assess($submission);
    }
}
