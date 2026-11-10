<?php

namespace App\Models;

use CodeIgniter\Model;

class ApprovalHistoryModel extends Model
{
    protected $table            = 'ApprovalHistory';
    protected $primaryKey       = 'ApprovalHistoryID';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'SubmissionID',
        'PersonnelID',
        'StepNumber',
        'Decision',
        'IsOverride',
        'DateCreated',
    ];

    public function forSubmission(int $submissionId): array
    {
        return $this->where('SubmissionID', $submissionId)
            ->orderBy('StepNumber', 'ASC')
            ->findAll();
    }
}
