<?php

namespace App\Controllers;

use App\Models\SubmissionTypeModel;
use CodeIgniter\RESTful\ResourceController;

class SubmissionTypeController extends ResourceController
{
    protected $modelName = SubmissionTypeModel::class;
    protected $format    = 'json';

    // GET /submission-types
    // Returns only active types — what a Pekerja sees when creating a new Submission
    public function index()
    {
        $model = new SubmissionTypeModel();
        return $this->respond($model->activeOnly());
    }

    // GET /submission-types/all
    // Returns everything, including inactive — for Admin views
    public function all()
    {
        $model = new SubmissionTypeModel();
        return $this->respond($model->findAll());
    }

    // GET /submission-types/{id}
    // Returns one SubmissionType along with its ApprovalStep rows
    public function show($id = null)
    {
        $model = new SubmissionTypeModel();
        $type  = $model->withSteps((int) $id);

        if ($type === null) {
            return $this->failNotFound('SubmissionType not found');
        }

        return $this->respond($type);
    }

    // POST /submission-types
    // Admin creates a new SubmissionType
    public function create()
    {
        $model = new SubmissionTypeModel();

        $data = [
            'PersonnelID' => $this->request->getVar('PersonnelID'),
            'Title'       => $this->request->getVar('Title'),
            'Description' => $this->request->getVar('Description'),
            'DateCreated' => date('Y-m-d H:i:s'),
            'IsActive'    => true,
        ];

        if (empty($data['PersonnelID']) || empty($data['Title'])) {
            return $this->failValidationErrors('PersonnelID and Title are required');
        }

        $id = $model->insert($data, true);
        return $this->respondCreated(['SubmissionTypeID' => $id]);
    }

    // PUT /submission-types/{id}/deactivate
    // Retires a type (hides it from the picker) without deleting it,
    // per the IsActive design decision — does NOT delete or edit the row itself
    public function deactivate($id = null)
    {
        $model = new SubmissionTypeModel();

        if ($model->find($id) === null) {
            return $this->failNotFound('SubmissionType not found');
        }

        $model->update($id, ['IsActive' => false]);
        return $this->respondUpdated(['message' => 'SubmissionType deactivated']);
    }
}
