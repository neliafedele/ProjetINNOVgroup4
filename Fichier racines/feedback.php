<?php
header('Content-Type: application/json; charset=utf-8');

$feedbackFile = __DIR__ . '/feedbacks.json';

$allowedTypes = ['general', 'dish'];

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

function safeTrim($value) {
    return trim((string) $value);
}

function truncated($value, $maxLength) {
    return mb_substr(safeTrim($value), 0, $maxLength);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $all = readFeedbacks($feedbackFile);

    $typeFilter = isset($_GET['type']) ? safeTrim($_GET['type']) : '';
    $dishIdFilter = isset($_GET['dishId']) ? safeTrim($_GET['dishId']) : '';

    if ($typeFilter !== '') {
        $all = array_values(array_filter($all, function ($item) use ($typeFilter) {
            $itemType = isset($item['type']) ? (string) $item['type'] : 'general';
            return $itemType === $typeFilter;
        }));
    }

    if ($dishIdFilter !== '') {
        $all = array_values(array_filter($all, function ($item) use ($dishIdFilter) {
            return isset($item['dishId']) && (string) $item['dishId'] === $dishIdFilter;
        }));
    }

    echo json_encode($all, JSON_UNESCAPED_UNICODE);
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

$type = isset($payload['type']) ? safeTrim($payload['type']) : 'general';
if (!in_array($type, $allowedTypes, true)) {
    $type = 'general';
}

$stars = isset($payload['stars']) ? (int) $payload['stars'] : 0;
$cat = isset($payload['cat']) ? safeTrim($payload['cat']) : '';
$food = isset($payload['food']) ? safeTrim($payload['food']) : '';
$text = isset($payload['text']) ? safeTrim($payload['text']) : '';
$name = isset($payload['name']) ? safeTrim($payload['name']) : 'Anonyme';
$dishId = isset($payload['dishId']) ? safeTrim($payload['dishId']) : '';

if ($stars < 0 || $stars > 5) {
    http_response_code(422);
    echo json_encode(['error' => 'La note doit être entre 0 et 5'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($stars === 0 && $text === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Ajoutez une note ou un commentaire'], JSON_UNESCAPED_UNICODE);
    exit;
}

$entry = [
    'type' => $type,
    'stars' => $stars,
    'name' => truncated($name === '' ? 'Anonyme' : $name, 60),
    'cat' => truncated($cat, 30),
    'food' => truncated($food, 80),
    'dishId' => truncated($dishId, 80),
    'text' => truncated($text, 600),
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