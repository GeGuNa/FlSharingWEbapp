<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $auth = requireAuth();
 $userId = $auth['user_id'];
 $db = Database::getInstance()->getConnection();

if (empty($_FILES['avatar'])) {
    jsonResponse(['error' => 'No avatar file provided'], 422);
}

 $file = $_FILES['avatar'];
 $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($file['type'], $allowedTypes)) {
    jsonResponse(['error' => 'Avatar must be JPEG, PNG, GIF, or WebP'], 422);
}
if ($file['size'] > 2097152) { 
    jsonResponse(['error' => 'Avatar must be under 2MB'], 422);
}


 $imageInfo = getimagesize($file['tmp_name']);
if (!$imageInfo) {
    jsonResponse(['error' => 'Invalid image file'], 422);
}

 $userDir = UPLOAD_DIR . '/' . $userId;
if (!is_dir($userDir)) {
    mkdir($userDir, 0755, true);
}

 $ext = image_type_to_extension($imageInfo[2], false);
 $storedName = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $ext;
 $filePath = $userDir . '/' . $storedName;


 $stmt = $db->prepare('SELECT avatar FROM users WHERE id = ?');
 $stmt->execute([$userId]);
 $oldAvatar = $stmt->fetch()['avatar'];
if ($oldAvatar && file_exists(__DIR__ . '/../../..' . $oldAvatar)) {
    unlink(__DIR__ . '/../../..' . $oldAvatar);
}

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    jsonResponse(['error' => 'Failed to save avatar'], 500);
}

 $avatarUrl = UPLOAD_URL . '/' . $userId . '/' . $storedName;
 $stmt = $db->prepare('UPDATE users SET avatar = ? WHERE id = ?');
 $stmt->execute([$avatarUrl, $userId]);

jsonResponse([
    'message' => 'Avatar updated',
    'avatar' => $avatarUrl
]);
