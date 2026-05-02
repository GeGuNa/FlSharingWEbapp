<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $auth = requireAuth();
 $userId = $auth['user_id'];
 $db = Database::getInstance()->getConnection();

 $input = getInput();
 $fileId = (int)($input['id'] ?? 0);

if ($fileId <= 0) {
    jsonResponse(['error' => 'Valid file ID is required'], 422);
}

 $stmt = $db->prepare(
    'SELECT id, file_path, file_size FROM files WHERE id = ? AND user_id = ? LIMIT 1'
);
 $stmt->execute([$fileId, $userId]);
 $file = $stmt->fetch();

if (!$file) {
    jsonResponse(['error' => 'File not found'], 404);
}

if (file_exists($file['file_path'])) {
    unlink($file['file_path']);
}

 $db->beginTransaction();
try {
    $stmt = $db->prepare('DELETE FROM files WHERE id = ?');
    $stmt->execute([$fileId]);

    $db->prepare('UPDATE users SET storage_used = GREATEST(0, storage_used - ?) WHERE id = ?')
        ->execute([$file['file_size'], $userId]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['error' => 'Failed to delete file'], 500);
}

jsonResponse(['message' => 'File deleted successfully']);
