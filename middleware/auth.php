<?php
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../utils/response.php';

function requireAuth(): array {
    $token = getAuthHeader();
    if (!$token) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }

    $payload = JWT::decode($token);
    if (!$payload) {
        jsonResponse(['error' => 'Invalid or expired token'], 401);
    }

    return $payload;
}

function optionalAuth(): ?array {
    $token = getAuthHeader();
    if (!$token) return null;
    return JWT::decode($token);
}
