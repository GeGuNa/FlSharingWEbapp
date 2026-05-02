<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

 $input = getInput();
 $name = sanitize($input['name'] ?? '');
 $email = sanitize($input['email'] ?? '');
 $subject = sanitize($input['subject'] ?? '');
 $message = sanitize($input['message'] ?? '');

if (empty($name) || strlen($name) > 100) {
    jsonResponse(['error' => 'Name is required (max 100 chars)'], 422);
}
if (!validateEmail($email)) {
    jsonResponse(['error' => 'Valid email is required'], 422);
}
if (empty($subject) || strlen($subject) > 255) {
    jsonResponse(['error' => 'Subject is required (max 255 chars)'], 422);
}
if (empty($message) || strlen($message) > 5000) {
    jsonResponse(['error' => 'Message is required (max 5000 chars)'], 422);
}


 $db = Database::getInstance()->getConnection();
 $stmt = $db->prepare(
    'SELECT COUNT(*) as cnt FROM contact_messages
     WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
);
 $stmt->execute([$email]);
if ((int)$stmt->fetch()['cnt'] >= 5) {
    jsonResponse(['error' => 'Too many messages. Please try again later.'], 429);
}

 $stmt = $db->prepare(
    'INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)'
);
 $stmt->execute([$name, $email, $subject, $message]);

jsonResponse(['message' => 'Message sent successfully. We will get back to you soon.'], 201);
