<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $input = getInput();
 $email = sanitize($input['email'] ?? '');
 $password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(['error' => 'Email and password are required'], 422);
}

 $db = Database::getInstance()->getConnection();

 $stmt = $db->prepare(
    'SELECT id, username, email, password, avatar, bio, is_active FROM users WHERE email = ? LIMIT 1'
);
 $stmt->execute([$email]);
 $user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(['error' => 'Invalid email or password'], 401);
}

if (!$user['is_active']) {
    jsonResponse(['error' => 'Account is deactivated'], 403);
}

 $token = JWT::encode([
    'user_id' => (int)$user['id'],
    'username' => $user['username']
]);

unset($user['password']);

jsonResponse([
    'message' => 'Login successful',
    'token' => $token,
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'avatar' => $user['avatar'],
        'bio' => $user['bio'],
    ]
]);
