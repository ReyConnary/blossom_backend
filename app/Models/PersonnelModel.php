<?php

namespace App\Models;

use CodeIgniter\Model;

class PersonnelModel extends Model
{
    protected $table            = 'Personnel';
    protected $primaryKey       = 'PersonnelID';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'SupervisorID',
        'ClearanceID',
        'FullName',
        'PhoneNo',
        'Email',
        'DateJoined',
        'DateLeave',
        'IsActive',
    ];

    // Find the Personnel record that is designated as Master
    // (highest ClearanceRank). Used as the escalation fallback target.
    public function findMaster(): ?array
    {
        return $this->select('Personnel.*')
            ->join('Clearance', 'Clearance.ClearanceID = Personnel.ClearanceID')
            ->where('Personnel.IsActive', true)
            ->orderBy('Clearance.ClearanceRank', 'DESC')
            ->first();
    }
}
