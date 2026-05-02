<?php
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$token = sanitize($_GET['token'] ?? '');
if (empty($token)) {
    jsonResponse(['error' => 'Share token is required'], 422);
}

 $db = Database::getInstance()->getConnection();

 $stmt = $db->prepare(
    'SELECT f.id, f.original_name, f.file_path, f.mime_type, f.file_size
     FROM files f
     WHERE f.share_token = ? AND f.is_public = 1
     LIMIT 1'
);
 $stmt->execute([$token]);
 $file = $stmt->fetch();

if (!$file || !file_exists($file['file_path'])) {
    jsonResponse(['error' => 'File not found or link is invalid'], 404);
}


 $db->prepare('UPDATE files SET downloads = downloads + 1 WHERE id = ?')
    ->execute([$file['id']]);


header('Content-Type: ' . $file['mime_type']);
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Content-Length: ' . $file['file_size']);
header('Cache-Control: no-cache');
readfile($file['file_path']);
exit;
