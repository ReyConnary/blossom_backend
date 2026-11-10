<?php

namespace App\Models;

use CodeIgniter\Model;

class SubmissionModel extends Model
{
    protected $table            = 'Submission';
    protected $primaryKey       = 'SubmissionID';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'SubmissionTypeID',
        'PersonnelID',
        'CurrentStep',
        'Title',
        'Description',
        'Status',
        'DateCreated',
        'DateFinished',
    ];

    // All Submissions created by a given Pekerja
    public function forPersonnel(int $personnelId): array
    {
        return $this->where('PersonnelID', $personnelId)
            ->orderBy('DateCreated', 'DESC')
            ->findAll();
    }
}
