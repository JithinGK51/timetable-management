<?php
/**
 * API: Get Classes by Institution
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$institutionId = isset($_GET['institution_id']) ? intval($_GET['institution_id']) : 0;

if (!$institutionId) {
    echo json_encode([]);
    exit;
}

$classes = dbFetchAll(
    "SELECT id, name FROM classes WHERE institution_id = ? AND status = 'active' ORDER BY name",
    [$institutionId]
);

echo json_encode($classes);
