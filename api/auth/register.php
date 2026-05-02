<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $input = getInput();
 $username = sanitize($input['username'] ?? '');
 $email = sanitize($input['email'] ?? '');
 $password = $input['password'] ?? '';
 $confirmPassword = $input['confirm_password'] ?? '';


if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
    jsonResponse(['error' => 'Username must be 3-50 characters'], 422);
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    jsonResponse(['error' => 'Username can only contain letters, numbers, and underscores'], 422);
}
if (!validateEmail($email)) {
    jsonResponse(['error' => 'Invalid email address'], 422);
}
if ($password !== $confirmPassword) {
    jsonResponse(['error' => 'Passwords do not match'], 422);
}
 $pwErrors = validatePassword($password);
if (!empty($pwErrors)) {
    jsonResponse(['error' => $pwErrors[0]], 422);
}

 $db = Database::getInstance()->getConnection();


 $stmt = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
 $stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'Email or username already taken'], 409);
}


 $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
 $stmt = $db->prepare(
    'INSERT INTO users (username, email, password) VALUES (?, ?, ?)'
);
 $stmt->execute([$username, $email, $hashed]);
 $userId = $db->lastInsertId();


 $token = JWT::encode(['user_id' => (int)$userId, 'username' => $username]);

jsonResponse([
    'message' => 'Registration successful',
    'token' => $token,
    'user' => [
        'id' => (int)$userId,
        'username' => $username,
        'email' => $email,
        'avatar' => null,
        'bio' => null,
    ]
], 201);
