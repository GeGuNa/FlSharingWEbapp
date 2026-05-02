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
 $fileId = (int)($input['id'] ?? 0);
 $isPublic = (int)($input['is_public'] ?? 0);

if ($fileId <= 0) {
    jsonResponse(['error' => 'Valid file ID is required'], 422);
}

 $stmt = $db->prepare(
    'SELECT id, share_token, is_public FROM files WHERE id = ? AND user_id = ? LIMIT 1'
);
 $stmt->execute([$fileId, $userId]);
 $file = $stmt->fetch();

if (!$file) {
    jsonResponse(['error' => 'File not found'], 404);
}

 $shareToken = $file['share_token'];

if ($isPublic && !$shareToken) {

    $shareToken = generateToken(32);

    while (true) {
        $stmt = $db->prepare('SELECT id FROM files WHERE share_token = ? LIMIT 1');
        $stmt->execute([$shareToken]);
        if (!$stmt->fetch()) break;
        $shareToken = generateToken(32);
    }
}

if (!$isPublic) {
    $shareToken = null;
}

 $stmt = $db->prepare(
    'UPDATE files SET is_public = ?, share_token = ? WHERE id = ? AND user_id = ?'
);
 $stmt->execute([$isPublic, $shareToken, $fileId, $userId]);

jsonResponse([
    'message' => $isPublic ? 'File is now shared' : 'Sharing disabled',
    'share_url' => $shareToken ? APP_URL . '/s/' . $shareToken : null,
    'is_public' => (bool)$isPublic,
]);
