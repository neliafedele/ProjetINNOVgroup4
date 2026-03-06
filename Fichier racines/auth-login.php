<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

$role = isset($payload['role']) ? strtolower((string) $payload['role']) : '';
$user = isset($payload['user']) ? strtolower(trim((string) $payload['user'])) : '';
$pass = isset($payload['pass']) ? (string) $payload['pass'] : '';

$creds = [
    'student' => [
        ['user' => 'etudiant', 'pass' => 'rak2025', 'name' => 'Étudiant'],
        ['user' => 'alice.martin', 'pass' => 'rak2025', 'name' => 'Alice'],
        ['user' => 'lucas.dupont', 'pass' => 'rak2025', 'name' => 'Lucas'],
    ],
    'staff' => [
        ['user' => 'personnel', 'pass' => 'rak2025', 'name' => 'Équipe RAK'],
        ['user' => 'chef.rak', 'pass' => 'rak2025', 'name' => 'Chef RAK'],
    ],
];

if (!isset($creds[$role])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'invalid_credentials']);
    exit;
}

$matched = null;
foreach ($creds[$role] as $entry) {
    if ($entry['user'] === $user && $entry['pass'] === $pass) {
        $matched = $entry;
        break;
    }
}

if ($matched === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'invalid_credentials']);
    exit;
}

$_SESSION['role'] = $role;
$_SESSION['user'] = [
    'user' => $matched['user'],
    'name' => $matched['name'],
];

echo json_encode([
    'ok' => true,
    'role' => $role,
    'user' => [
        'user' => $matched['user'],
        'name' => $matched['name'],
    ],
]);
