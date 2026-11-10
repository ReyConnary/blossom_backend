<?php

namespace App\Models;

use CodeIgniter\Model;

class SubmissionTypeModel extends Model
{
    protected $table            = 'SubmissionType';
    protected $primaryKey       = 'SubmissionTypeID';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'PersonnelID',
        'Title',
        'Description',
        'DateCreated',
        'IsActive',
    ];

    // Returns a SubmissionType along with its ApprovalStep rows
    public function withSteps(int $submissionTypeId): ?array
    {
        $type = $this->find($submissionTypeId);
        if ($type === null) {
            return null;
        }

        $stepModel = new ApprovalStepModel();
        $type['ApprovalSteps'] = $stepModel
            ->where('SubmissionTypeID', $submissionTypeId)
            ->orderBy('StepNumber', 'ASC')
            ->findAll();

        return $type;
    }

    // Only types still active — for the Pekerja's "create new submission" picker
    public function activeOnly(): array
    {
        return $this->where('IsActive', true)->findAll();
    }
}
