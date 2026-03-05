<?php
header('Content-Type: application/json; charset=utf-8');

$feedbackFile = __DIR__ . '/feedbacks.json';

function readFeedbacks($feedbackFile) {
    if (!file_exists($feedbackFile)) {
        return [];
    }

    $raw = file_get_contents($feedbackFile);
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(readFeedbacks($feedbackFile), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format JSON invalide'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stars = isset($payload['stars']) ? (int) $payload['stars'] : 0;
$cat = isset($payload['cat']) ? trim((string) $payload['cat']) : '';
$food = isset($payload['food']) ? trim((string) $payload['food']) : '';
$text = isset($payload['text']) ? trim((string) $payload['text']) : '';

if ($stars < 1 || $stars > 5) {
    http_response_code(422);
    echo json_encode(['error' => 'La note doit être entre 1 et 5'], JSON_UNESCAPED_UNICODE);
    exit;
}

$entry = [
    'stars' => $stars,
    'cat' => mb_substr($cat, 0, 30),
    'food' => mb_substr($food, 0, 80),
    'text' => mb_substr($text, 0, 600),
    'time' => date('Y-m-d H:i:s')
];

$allFeedbacks = readFeedbacks($feedbackFile);
array_unshift($allFeedbacks, $entry);

if (count($allFeedbacks) > 300) {
    $allFeedbacks = array_slice($allFeedbacks, 0, 300);
}

$ok = file_put_contents(
    $feedbackFile,
    json_encode($allFeedbacks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

if ($ok === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de sauvegarder le feedback'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true, 'item' => $entry], JSON_UNESCAPED_UNICODE);
?>