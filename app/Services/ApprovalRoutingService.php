<?php

namespace App\Services;

use App\Models\PersonnelModel;
use App\Models\ApprovalStepModel;

class ApprovalRoutingService
{
    private const MAX_HOPS = 20;

    protected PersonnelModel $personnelModel;
    protected ApprovalStepModel $approvalStepModel;

    public function __construct()
    {
        $this->personnelModel    = new PersonnelModel();
        $this->approvalStepModel = new ApprovalStepModel();
    }

    /**
     * Finds the Personnel who should approve a Submission at a given step.
     *
     * Walks up the SupervisorID chain starting from the submitter, looking
     * for the first active Personnel whose ClearanceID matches the
     * ClearanceID required by the ApprovalStep for (submissionTypeId, stepNumber).
     *
     * - Capped at MAX_HOPS (20) to guard against circular/corrupted chains.
     * - If no match is found within the cap, OR the chain runs out
     *   (reaches a Personnel with no SupervisorID) before a match is found,
     *   falls back to the Master (highest ClearanceRank, active).
     *
     * @return array{personnel: array|null, escalated: bool, hopsUsed: int}
     */
    public function findApprover(int $submitterPersonnelId, int $submissionTypeId, int $stepNumber): array
    {
        $step = $this->approvalStepModel->findStep($submissionTypeId, $stepNumber);

        if ($step === null) {
            // No ApprovalStep configured for this step — treat as a
            // configuration gap, same as "no match found": escalate to Master.
            return $this->escalateToMaster(0);
        }

        $requiredClearanceId = (int) $step['ClearanceID'];

        $currentPersonnel = $this->personnelModel->find($submitterPersonnelId);
        $hops = 0;

        while ($currentPersonnel !== null && $hops < self::MAX_HOPS) {
            $supervisorId = $currentPersonnel['SupervisorID'] ?? null;

            if ($supervisorId === null) {
                // Reached the top of the chain with no match — escalate.
                return $this->escalateToMaster($hops);
            }

            $supervisor = $this->personnelModel->find($supervisorId);
            $hops++;

            if ($supervisor === null || ! $supervisor['IsActive']) {
                // Broken chain or inactive supervisor — keep walking up
                // past them is not possible without their own SupervisorID,
                // so treat as a dead end and escalate.
                return $this->escalateToMaster($hops);
            }

            if ((int) $supervisor['ClearanceID'] === $requiredClearanceId) {
                return [
                    'personnel' => $supervisor,
                    'escalated' => false,
                    'hopsUsed'  => $hops,
                ];
            }

            $currentPersonnel = $supervisor;
        }

        // Exceeded MAX_HOPS without finding a match.
        return $this->escalateToMaster($hops);
    }

    private function escalateToMaster(int $hopsUsed): array
    {
        $master = $this->personnelModel->findMaster();

        return [
            'personnel' => $master,
            'escalated' => true,
            'hopsUsed'  => $hopsUsed,
        ];
    }
}
