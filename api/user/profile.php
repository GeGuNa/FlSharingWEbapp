<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../middleware/auth.php';

 $auth = requireAuth();
 $userId = $auth['user_id'];
 $db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare(
        'SELECT id, username, email, avatar, bio, phone, storage_used, created_at FROM users WHERE id = ?'
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    $stmt = $db->prepare(
        'SELECT COUNT(*) as total_files, SUM(file_size) as total_size,
                SUM(downloads) as total_downloads
         FROM files WHERE user_id = ?'
    );
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();

    $user['id'] = (int)$user['id'];
    $user['storage_used'] = (int)$user['storage_used'];
    $user['stats'] = [
        'total_files' => (int)$stats['total_files'],
        'total_size' => (int)$stats['total_size'],
        'total_downloads' => (int)$stats['total_downloads'],
    ];

    jsonResponse(['user' => $user]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = getInput();
    $username = sanitize($input['username'] ?? '');
    $bio = sanitize($input['bio'] ?? '');
    $phone = sanitize($input['phone'] ?? '');

    if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
        jsonResponse(['error' => 'Username must be 3-50 characters'], 422);
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        jsonResponse(['error' => 'Username can only contain letters, numbers, and underscores'], 422);
    }


    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
    $stmt->execute([$username, $userId]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Username already taken'], 409);
    }

    $stmt = $db->prepare(
        'UPDATE users SET username = ?, bio = ?, phone = ? WHERE id = ?'
    );
    $stmt->execute([$username, $bio, $phone, $userId]);

    jsonResponse(['message' => 'Profile updated successfully']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
