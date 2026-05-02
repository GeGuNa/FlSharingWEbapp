<?php
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $input = getInput();
 $token = sanitize($input['token'] ?? '');
 $password = $input['password'] ?? '';
 $confirmPassword = $input['confirm_password'] ?? '';

if (empty($token) || strlen($token) < 32) {
    jsonResponse(['error' => 'Invalid recovery token'], 422);
}
if ($password !== $confirmPassword) {
    jsonResponse(['error' => 'Passwords do not match'], 422);
}
 $pwErrors = validatePassword($password);
if (!empty($pwErrors)) {
    jsonResponse(['error' => $pwErrors[0]], 422);
}

 $db = Database::getInstance()->getConnection();

 $stmt = $db->prepare(
    'SELECT pr.id, pr.user_id, pr.expires_at FROM password_resets pr
     WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
     LIMIT 1'
);
 $stmt->execute([$token]);
 $reset = $stmt->fetch();

if (!$reset) {
    jsonResponse(['error' => 'Invalid or expired recovery token'], 400);
}

 $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

 $db->beginTransaction();
try {
    $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([$hashed, $reset['user_id']]);

    $stmt = $db->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
    $stmt->execute([$reset['id']]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['error' => 'Failed to reset password'], 500);
}

jsonResponse(['message' => 'Password has been reset successfully']);
