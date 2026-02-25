<?php
/**
 * API: Get Sections by Class
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$classId = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if (!$classId) {
    echo json_encode([]);
    exit;
}

$sections = dbFetchAll(
    "SELECT id, name FROM sections WHERE class_id = ? AND status = 'active' ORDER BY name",
    [$classId]
);

echo json_encode($sections);
