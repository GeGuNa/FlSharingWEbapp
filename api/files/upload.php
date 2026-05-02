<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $auth = requireAuth();
 $userId = $auth['user_id'];
 $db = Database::getInstance()->getConnection();

if (empty($_FILES) && empty($_POST['files_json'])) {
    jsonResponse(['error' => 'No files provided'], 422);
}

 $allowed = unserialize(ALLOWED_EXTENSIONS);
 $userDir = UPLOAD_DIR . '/' . $userId;

if (!is_dir($userDir)) {
    mkdir($userDir, 0755, true);
}

 $results = [];


 $files = isset($_FILES['files']) && is_array($_FILES['files']['name'])
    ? $_FILES['files']
    : null;


 $base64Files = json_decode($_POST['files_json'] ?? '[]', true);

if ($files) {
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $results[] = [
                'name' => $files['name'][$i],
                'success' => false,
                'error' => 'Upload error code: ' . $files['error'][$i]
            ];
            continue;
        }

        $originalName = sanitize(basename($files['name'][$i]));
        $tmpPath = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $results[] = [
                'name' => $originalName, 'success' => false,
                'error' => 'File type not allowed'
            ];
            continue;
        }

        if ($fileSize > UPLOAD_MAX_SIZE) {
            $results[] = [
                'name' => $originalName, 'success' => false,
                'error' => 'File exceeds maximum size'
            ];
            continue;
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $filePath = $userDir . '/' . $storedName;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath) ?: 'application/octet-stream';

        if (!move_uploaded_file($tmpPath, $filePath)) {
            $results[] = [
                'name' => $originalName, 'success' => false,
                'error' => 'Failed to save file'
            ];
            continue;
        }

        $stmt = $db->prepare(
            'INSERT INTO files (user_id, original_name, stored_name, file_path, file_size, file_type, mime_type)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId, $originalName, $storedName,
            $filePath, $fileSize, $ext, $mimeType
        ]);

        $db->prepare('UPDATE users SET storage_used = storage_used + ? WHERE id = ?')
            ->execute([$fileSize, $userId]);

        $results[] = [
            'name' => $originalName, 'success' => true,
            'file' => ['id' => (int)$db->lastInsertId()]
        ];
    }
}

 $failed = count(array_filter($results, fn($r) => !$r['success']));
 $successCount = count($results) - $failed;

jsonResponse([
    'message' => sprintf('%d file(s) uploaded successfully', $successCount),
    'results' => $results,
    'failed' => $failed
], $failed > 0 && $successCount === 0 ? 400 : 200);
