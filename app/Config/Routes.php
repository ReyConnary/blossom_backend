<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

    // Login
$routes->post('api/login', 'Api\AuthController::login');

$routes->group('api', ['filter' => 'jwtAuth'], function ($routes) {

    // SubmissionType
    $routes->get('submission-types', 'SubmissionTypeController::index');       // active only
    $routes->get('submission-types/all', 'SubmissionTypeController::all');     // all, incl. inactive
    $routes->get('submission-types/(:num)', 'SubmissionTypeController::show/$1');
    $routes->post('submission-types', 'SubmissionTypeController::create');
    $routes->put('submission-types/(:num)/deactivate', 'SubmissionTypeController::deactivate/$1');

    // ApprovalStep (nested under SubmissionType)
    $routes->get('submission-types/(:num)/steps', 'ApprovalStepController::index/$1');
    $routes->post('submission-types/(:num)/steps', 'ApprovalStepController::create/$1');

    // Submission
    $routes->post('submissions', 'SubmissionController::create');
    $routes->post('submissions/(:num)/decision', 'SubmissionController::decision/$1');
    $routes->get('submissions/personnel/(:num)', 'SubmissionController::forPersonnel/$1');
    $routes->get('submissions/(:num)/history', 'SubmissionController::history/$1');
    $routes->get('submissions/pending/(:num)', 'SubmissionController::pendingFor/$1');

});