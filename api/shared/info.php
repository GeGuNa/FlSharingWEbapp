<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$token = sanitize($_GET['token'] ?? '');
if (empty($token)) {
    jsonResponse(['error' => 'Share token is required'], 422);
}

 $db = Database::getInstance()->getConnection();

 $stmt = $db->prepare(
    'SELECT f.id, f.original_name, f.file_size, f.file_type, f.mime_type,
            f.downloads, f.created_at, u.username
     FROM files f
     JOIN users u ON u.id = f.user_id
     WHERE f.share_token = ? AND f.is_public = 1
     LIMIT 1'
);
 $stmt->execute([$token]);
 $file = $stmt->fetch();

if (!$file) {
    jsonResponse(['error' => 'File not found or link is invalid'], 404);
}

 $file['id'] = (int)$file['id'];
 $file['file_size'] = (int)$file['file_size'];
 $file['downloads'] = (int)$file['downloads'];

jsonResponse(['file' => $file]);
