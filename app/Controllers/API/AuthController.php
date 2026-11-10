<?php
// app/Controllers/Api/AuthController.php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PersonnelModel;

class AuthController extends BaseController
{
public function login()
{
    $json = $this->request->getJSON(true);

    $email    = $json['email'] ?? null;
    $password = $json['password'] ?? null;

    if (!$email || !$password) {
        return $this->response->setStatusCode(400)->setJSON([
            'status'  => 'error',
            'message' => 'Email and password are required',
        ]);
    }

    $personnelModel = new PersonnelModel();
    $user = $personnelModel->where('Email', $email)->first();

log_message('debug', 'LOGIN ATTEMPT email=' . $email);
log_message('debug', 'USER FOUND: ' . ($user ? 'yes' : 'no'));
if ($user) {
    log_message('debug', 'STORED HASH: ' . $user['password']);
    log_message('debug', 'VERIFY RESULT: ' . (password_verify($password, $user['password']) ? 'true' : 'false'));
}

if (!$user || !password_verify($password, $user['password'])) {
    return $this->response->setStatusCode(401)->setJSON([
        'status'  => 'error',
        'message' => 'Invalid email or password',
    ]);
}

    if (!$user['IsActive']) {
        return $this->response->setStatusCode(403)->setJSON([
            'status'  => 'error',
            'message' => 'Account is inactive',
        ]);
    }

$accessToken = generate_jwt([
    'personnel_id' => (int) $user['PersonnelID'],
    'clearance_id' => (int) $user['ClearanceID'],
], expiry: 3600);

$refreshToken = generate_refresh_token((int) $user['PersonnelID']);

return $this->response->setJSON([
    'status' => 'success',
    'data' => [
        'token'         => $accessToken,
        'refresh_token' => $refreshToken,
        'personnel_id'  => (int) $user['PersonnelID'],
        'full_name'     => $user['FullName'],
        'clearance_id'  => (int) $user['ClearanceID'],
    ],
]);
}
}