<?php
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $input = getInput();
 $email = sanitize($input['email'] ?? '');

if (!validateEmail($email)) {
    jsonResponse(['error' => 'Valid email is required'], 422);
}

 $db = Database::getInstance()->getConnection();

 $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
 $stmt->execute([$email]);
 $user = $stmt->fetch();

if (!$user) {
    jsonResponse(['message' => 'If the email exists, a recovery link has been sent']);
}

 $token = generateToken(32);
 $expiresAt = date('Y-m-d H:i:s', time() + 3600); 

 $stmt = $db->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0');
 $stmt->execute([$user['id']]);

 $stmt = $db->prepare(
    'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)'
);
 $stmt->execute([$user['id'], $token, $expiresAt]);


// mail($email, 'Password Recovery', APP_URL . '/reset-password?token=' . $token, ...);


jsonResponse([
    'message' => 'If the email exists, a recovery link has been sent',
    'debug_token' => $token 
]);
