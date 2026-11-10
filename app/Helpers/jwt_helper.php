<?php
// app/Helpers/jwt_helper.php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\JWT as JWTConfig;

if (!function_exists('generate_jwt')) {
    function generate_jwt(array $claims, int $expiry = 3600): string
    {
        $config = new JWTConfig();
        $issuedAt = time();

        $payload = array_merge($claims, [
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expiry,
        ]);

        return JWT::encode($payload, $config->secretKey, 'HS256');
    }
}

if (!function_exists('verify_jwt')) {
    function verify_jwt(string $token): ?array
    {
        $config = new JWTConfig();
        try {
            $decoded = JWT::decode($token, new Key($config->secretKey, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null; // expired, malformed, or invalid signature
        }
    }
}

if (!function_exists('generate_refresh_token')) {
    function generate_refresh_token(int $personnelId): string
    {
        // Refresh tokens live much longer and should be opaque + stored server-side
        // so they can be revoked (e.g. on logout or password change).
        $token = bin2hex(random_bytes(32));

        $db = \Config\Database::connect();
        $db->table('refresh_tokens')->insert([
            'personnel_id' => $personnelId,
            'token'        => hash('sha256', $token), // store hashed, never plaintext
            'expires_at'   => date('Y-m-d H:i:s', strtotime('+30 days')),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }
}