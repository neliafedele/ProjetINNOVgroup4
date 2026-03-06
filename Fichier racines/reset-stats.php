<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

if (($_SESSION['role'] ?? null) !== 'staff') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
$periodes = ['midi', 'soir'];

$stats = [];
foreach ($jours as $jour) {
    $stats[$jour] = [];
    foreach ($periodes as $periode) {
        $stats[$jour][$periode] = [
            'oui' => 0,
            'non' => 0,
            'peut-etre' => 0,
        ];
    }
}

$ok = file_put_contents(
    __DIR__ . '/stats.json',
    json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

header('Content-Type: application/json; charset=utf-8');
if ($ok === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'write_failed']);
    exit;
}

echo json_encode(['ok' => true]);
