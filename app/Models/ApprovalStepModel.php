<?php

namespace App\Models;

use CodeIgniter\Model;

class ApprovalStepModel extends Model
{
    protected $table            = 'ApprovalStep';
    protected $primaryKey       = 'ApprovalStepID';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'SubmissionTypeID',
        'StepNumber',
        'ClearanceID',
    ];

    // Required clearance for a given SubmissionType + step number.
    // Used by the approval routing logic (Phase 1, step 3).
    public function findStep(int $submissionTypeId, int $stepNumber): ?array
    {
        return $this->where('SubmissionTypeID', $submissionTypeId)
            ->where('StepNumber', $stepNumber)
            ->first();
    }

    // Total number of steps configured for a SubmissionType —
    // used to know when CurrentStep is the final step.
    public function countSteps(int $submissionTypeId): int
    {
        return $this->where('SubmissionTypeID', $submissionTypeId)->countAllResults();
    }
}
