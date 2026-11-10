<?php

namespace App\Controllers;

use App\Models\SubmissionModel;
use App\Models\SubmissionTypeModel;
use App\Models\ApprovalStepModel;
use App\Models\ApprovalHistoryModel;
use App\Models\PersonnelModel;
use App\Services\ApprovalRoutingService;
use CodeIgniter\RESTful\ResourceController;

class SubmissionController extends ResourceController
{
    protected $format = 'json';

    // =====================================================
    // POST /submissions
    // Pekerja creates a Submission against an existing SubmissionType
    // =====================================================
    public function create()
    {
        $submissionModel     = new SubmissionModel();
        $submissionTypeModel = new SubmissionTypeModel();

        $submissionTypeId = $this->request->getVar('SubmissionTypeID');
        $personnelId      = $this->request->getVar('PersonnelID');
        $title            = $this->request->getVar('Title');
        $description      = $this->request->getVar('Description');

        if (empty($submissionTypeId) || empty($personnelId) || empty($title)) {
            return $this->failValidationErrors('SubmissionTypeID, PersonnelID, and Title are required');
        }

        $type = $submissionTypeModel->find($submissionTypeId);
        if ($type === null || ! $type['IsActive']) {
            return $this->fail('SubmissionType tidak ditemukan atau sudah tidak aktif', 400);
        }

        $data = [
            'SubmissionTypeID' => $submissionTypeId,
            'PersonnelID'      => $personnelId,
            'CurrentStep'      => 1,
            'Title'            => $title,
            'Description'      => $description,
            'Status'           => 'Pending',
            'DateCreated'      => date('Y-m-d H:i:s'),
            'DateFinished'     => null,
        ];

        $id = $submissionModel->insert($data, true);
        return $this->respondCreated(['SubmissionID' => $id]);
    }

    // =====================================================
    // POST /submissions/{id}/decision
    // Manager approves or rejects a Submission at its current step.
    // Wraps ApprovalRoutingService, writes ApprovalHistory, advances
    // CurrentStep or finalizes Status.
    // =====================================================
    public function decision($submissionId = null)
    {
        $submissionModel     = new SubmissionModel();
        $approvalStepModel   = new ApprovalStepModel();
        $approvalHistoryModel = new ApprovalHistoryModel();
        $routingService       = new ApprovalRoutingService();

        $submission = $submissionModel->find($submissionId);
        if ($submission === null) {
            return $this->failNotFound('Submission not found');
        }

        if ($submission['Status'] !== 'Pending') {
            // Mirrors Tabel 2.1 DFA: Approved/Rejected are terminal —
            // no transition is valid from either state.
            return $this->fail('Submission sudah berstatus final, tidak dapat diproses lebih lanjut', 409);
        }

        $decisionInput   = $this->request->getVar('Decision');   // 'Approved' | 'Rejected'
        $deciderId       = $this->request->getVar('PersonnelID'); // who is submitting the decision
        $isOverride      = (bool) $this->request->getVar('IsOverride') ?: false;

        if (! in_array($decisionInput, ['Approved', 'Rejected'], true)) {
            return $this->failValidationErrors("Decision must be 'Approved' or 'Rejected'");
        }

        // Verify the decider is actually the authorized approver for this step
        // (unless this is a Master override, which bypasses routing by design).
        if (! $isOverride) {
            $routing = $routingService->findApprover(
                (int) $submission['PersonnelID'],
                (int) $submission['SubmissionTypeID'],
                (int) $submission['CurrentStep']
            );

            $authorizedPersonnel = $routing['personnel'];
            if ($authorizedPersonnel === null || (int) $authorizedPersonnel['PersonnelID'] !== (int) $deciderId) {
                return $this->fail('Personnel ini tidak berwenang memutuskan pada tahap ini', 403);
            }
        }

        // Record the decision in ApprovalHistory
        $approvalHistoryModel->insert([
            'SubmissionID' => $submissionId,
            'PersonnelID'  => $deciderId,
            'StepNumber'   => $submission['CurrentStep'],
            'Decision'     => $decisionInput,
            'IsOverride'   => $isOverride,
            'DateCreated'  => date('Y-m-d H:i:s'),
        ]);

        if ($decisionInput === 'Rejected') {
            // Terminal, no further path — matches Submission DFA (Tabel 2.1)
            $submissionModel->update($submissionId, [
                'Status'       => 'Rejected',
                'DateFinished' => date('Y-m-d H:i:s'),
            ]);

            return $this->respond(['message' => 'Submission rejected', 'Status' => 'Rejected']);
        }

        // Decision was Approved — check if this was the final step
        $totalSteps = $approvalStepModel->countSteps((int) $submission['SubmissionTypeID']);
        $nextStep   = (int) $submission['CurrentStep'] + 1;

        if ($nextStep > $totalSteps) {
            // approveFinal — final step, Submission is now Approved
            $submissionModel->update($submissionId, [
                'Status'       => 'Approved',
                'DateFinished' => date('Y-m-d H:i:s'),
            ]);

            return $this->respond(['message' => 'Submission approved (final)', 'Status' => 'Approved']);
        }

        // approveContinue — more steps remain, advance CurrentStep, stay Pending
        $submissionModel->update($submissionId, [
            'CurrentStep' => $nextStep,
        ]);

        return $this->respond([
            'message'     => 'Submission approved, advancing to next step',
            'Status'      => 'Pending',
            'CurrentStep' => $nextStep,
        ]);
    }

    // =====================================================
    // GET /submissions/personnel/{personnelId}
    // List Submissions created by a given Pekerja
    // =====================================================
    public function forPersonnel($personnelId = null)
    {
        $model = new SubmissionModel();
        return $this->respond($model->forPersonnel((int) $personnelId));
    }

    // =====================================================
    // GET /submissions/{id}/history
    // View ApprovalHistory for a Submission
    // =====================================================
    public function history($submissionId = null)
    {
        $model = new ApprovalHistoryModel();
        return $this->respond($model->forSubmission((int) $submissionId));
    }

    // =====================================================
    // GET /submissions/pending/{personnelId}
    // List Submissions pending THIS Personnel's decision —
    // i.e. where this Personnel is the currently-authorized approver
    // =====================================================
    public function pendingFor($personnelId = null)
    {
        $submissionModel = new SubmissionModel();
        $routingService  = new ApprovalRoutingService();

        $allPending = $submissionModel->where('Status', 'Pending')->findAll();
        $result = [];

        foreach ($allPending as $submission) {
            $routing = $routingService->findApprover(
                (int) $submission['PersonnelID'],
                (int) $submission['SubmissionTypeID'],
                (int) $submission['CurrentStep']
            );

            $authorizedPersonnel = $routing['personnel'];
            if ($authorizedPersonnel !== null && (int) $authorizedPersonnel['PersonnelID'] === (int) $personnelId) {
                $result[] = $submission;
            }
        }

        return $this->respond($result);
    }
}
