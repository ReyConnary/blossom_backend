<?php

namespace App\Controllers;

use App\Models\ApprovalStepModel;
use App\Models\SubmissionModel;
use CodeIgniter\RESTful\ResourceController;

class ApprovalStepController extends ResourceController
{
    protected $modelName = ApprovalStepModel::class;
    protected $format    = 'json';

    // GET /submission-types/{submissionTypeId}/steps
    public function index($submissionTypeId = null)
    {
        $model = new ApprovalStepModel();
        $steps = $model->where('SubmissionTypeID', $submissionTypeId)
            ->orderBy('StepNumber', 'ASC')
            ->findAll();

        return $this->respond($steps);
    }

    // POST /submission-types/{submissionTypeId}/steps
    // Admin adds a step to a SubmissionType.
    // Blocked once the SubmissionType is immutable (has >=1 Submission referencing it) —
    // per the SubmissionType immutability rule finalized in BAB II.
    public function create($submissionTypeId = null)
    {
        $submissionModel = new SubmissionModel();
        $hasSubmissions = $submissionModel
            ->where('SubmissionTypeID', $submissionTypeId)
            ->countAllResults() > 0;

        if ($hasSubmissions) {
            return $this->fail(
                'SubmissionType sudah digunakan oleh Submission yang ada — tidak dapat diubah. '
                . 'Buat SubmissionType baru untuk perubahan alur persetujuan.',
                409
            );
        }

        $model = new ApprovalStepModel();
        $data = [
            'SubmissionTypeID' => $submissionTypeId,
            'StepNumber'       => $this->request->getVar('StepNumber'),
            'ClearanceID'      => $this->request->getVar('ClearanceID'),
        ];

        if (empty($data['StepNumber']) || empty($data['ClearanceID'])) {
            return $this->failValidationErrors('StepNumber and ClearanceID are required');
        }

        $id = $model->insert($data, true);
        return $this->respondCreated(['ApprovalStepID' => $id]);
    }
}
