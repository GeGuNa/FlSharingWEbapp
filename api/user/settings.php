<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $auth = requireAuth();
 $userId = $auth['user_id'];
 $db = Database::getInstance()->getConnection();

 $input = getInput();
 $action = sanitize($input['action'] ?? '');

if ($action === 'change_password') {
    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        jsonResponse(['error' => 'All password fields are required'], 422);
    }
    if ($newPassword !== $confirmPassword) {
        jsonResponse(['error' => 'New passwords do not match'], 422);
    }
    $pwErrors = validatePassword($newPassword);
    if (!empty($pwErrors)) {
        jsonResponse(['error' => $pwErrors[0]], 422);
    }

    $stmt = $db->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($currentPassword, $user['password'])) {
        jsonResponse(['error' => 'Current password is incorrect'], 401);
    }

    $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([$hashed, $userId]);

    jsonResponse(['message' => 'Password changed successfully']);
}

if ($action === 'delete_account') {
    $confirmPassword = $input['confirm_password'] ?? '';

    $stmt = $db->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($confirmPassword, $user['password'])) {
        jsonResponse(['error' => 'Password confirmation incorrect'], 401);
    }


    $userDir = UPLOAD_DIR . '/' . $userId;
    if (is_dir($userDir)) {
        $files = glob($userDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        rmdir($userDir);
    }


    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$userId]);

    jsonResponse(['message' => 'Account deleted successfully']);
}

jsonResponse(['error' => 'Invalid action'], 400);
