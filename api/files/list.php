<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $auth = requireAuth();
 $userId = $auth['user_id'];
 $db = Database::getInstance()->getConnection();

 $page = max(1, (int)($_GET['page'] ?? 1));
 $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
 $offset = ($page - 1) * $limit;
 $search = sanitize($_GET['search'] ?? '');
 $sortBy = in_array($_GET['sort'] ?? '', ['name','size','date','type'])
    ? $_GET['sort'] : 'date';
 $sortOrder = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
 $typeFilter = sanitize($_GET['type'] ?? '');

 $sortMap = [
    'name' => 'f.original_name',
    'size' => 'f.file_size',
    'date' => 'f.created_at',
    'type' => 'f.file_type',
];

 $where = 'WHERE f.user_id = ?';
 $params = [$userId];

if (!empty($search)) {
    $where .= ' AND f.original_name LIKE ?';
    $params[] = "%$search%";
}
if (!empty($typeFilter)) {
    $where .= ' AND f.file_type = ?';
    $params[] = $typeFilter;
}


 $countStmt = $db->prepare("SELECT COUNT(*) as total FROM files f $where");
 $countStmt->execute($params);
 $total = (int)$countStmt->fetch()['total'];


 $stmt = $db->prepare(
    "SELECT f.id, f.original_name, f.file_size, f.file_type, f.mime_type,
            f.share_token, f.is_public, f.downloads, f.created_at
     FROM files f $where
     ORDER BY {$sortMap[$sortBy]} $sortOrder
     LIMIT $limit OFFSET $offset"
);
 $stmt->execute($params);
 $files = $stmt->fetchAll();


 $stmt = $db->prepare('SELECT storage_used FROM users WHERE id = ?');
 $stmt->execute([$userId]);
 $storageUsed = (int)$stmt->fetch()['storage_used'];


 $stmt = $db->prepare(
    'SELECT file_type, COUNT(*) as count FROM files WHERE user_id = ? GROUP BY file_type ORDER BY count DESC'
);
 $stmt->execute([$userId]);
 $typeCounts = $stmt->fetchAll();

jsonResponse([
    'files' => array_map(function($f) {
        $f['id'] = (int)$f['id'];
        $f['file_size'] = (int)$f['file_size'];
        $f['downloads'] = (int)$f['downloads'];
        $f['is_public'] = (bool)$f['is_public'];
        $f['share_url'] = $f['share_token']
            ? APP_URL . '/s/' . $f['share_token']
            : null;
        return $f;
    }, $files),
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'pages' => (int)ceil($total / $limit),
    ],
    'storage_used' => $storageUsed,
    'type_filters' => $typeCounts,
]);
