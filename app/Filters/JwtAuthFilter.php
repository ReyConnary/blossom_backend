<?php
// app/Filters/JwtAuthFilter.php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return service('response')->setStatusCode(401)->setJSON([
                'status' => 'error', 'message' => 'Missing or invalid token',
            ]);
        }

        $token = substr($authHeader, 7);
        $decoded = verify_jwt($token);

        if ($decoded === null) {
            return service('response')->setStatusCode(401)->setJSON([
                'status' => 'error', 'message' => 'Token expired or invalid',
            ]);
        }

        $request->personnelId = $decoded['personnel_id'] ?? null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}