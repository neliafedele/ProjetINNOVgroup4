<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$storageFile = __DIR__ . '/participation-stats.json';

function currentWeekKey() {
    return date('o-\\WW');
}

function readParticipationData($storageFile) {
    if (!file_exists($storageFile)) {
        return [];
    }

    $raw = file_get_contents($storageFile);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function writeParticipationData($storageFile, $data) {
    return file_put_contents(
        $storageFile,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function normalizeWeekStats($stats) {
    $presence = isset($stats['presence']) ? (int) $stats['presence'] : 0;
    $menuVote = isset($stats['menu_vote']) ? (int) $stats['menu_vote'] : 0;
    $feedback = isset($stats['feedback']) ? (int) $stats['feedback'] : 0;
    return [
        'presence' => $presence,
        'menu_vote' => $menuVote,
        'feedback' => $feedback,
        'total' => $presence + $menuVote + $feedback
    ];
}

function getEntryWeekStats($entry, $weekKey) {
    $weeks = isset($entry['weeks']) && is_array($entry['weeks']) ? $entry['weeks'] : [];
    if (isset($weeks[$weekKey]) && is_array($weeks[$weekKey])) {
        return normalizeWeekStats($weeks[$weekKey]);
    }

    if (!empty($weeks)) {
        return normalizeWeekStats([
            'presence' => 0,
            'menu_vote' => 0,
            'feedback' => 0,
        ]);
    }

    $legacyWeekKey = isset($entry['weekKey']) ? (string) $entry['weekKey'] : '';
    if ($legacyWeekKey !== '' && $legacyWeekKey !== $weekKey) {
        return normalizeWeekStats([
            'presence' => 0,
            'menu_vote' => 0,
            'feedback' => 0,
        ]);
    }

    return normalizeWeekStats([
        'presence' => isset($entry['presence']) ? $entry['presence'] : 0,
        'menu_vote' => isset($entry['menu_vote']) ? $entry['menu_vote'] : 0,
        'feedback' => isset($entry['feedback']) ? $entry['feedback'] : 0,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $viewerRole = $_SESSION['role'] ?? null;
    if ($viewerRole !== 'staff' && $viewerRole !== 'student') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $weekKey = currentWeekKey();
    $all = readParticipationData($storageFile);

    $weekly = array_map(function ($entry) use ($weekKey) {
        $week = getEntryWeekStats($entry, $weekKey);
        return [
            'user' => isset($entry['user']) ? (string) $entry['user'] : '',
            'name' => isset($entry['name']) ? (string) $entry['name'] : '',
            'presence' => $week['presence'],
            'menu_vote' => $week['menu_vote'],
            'feedback' => $week['feedback'],
            'total' => $week['total'],
            'weekKey' => $weekKey,
            'lastActivity' => isset($entry['lastActivity']) ? (string) $entry['lastActivity'] : ''
        ];
    }, $all);

    usort($weekly, function ($a, $b) {
        return ((int)($b['total'] ?? 0)) <=> ((int)($a['total'] ?? 0));
    });

    echo json_encode($weekly, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SESSION['role'] ?? null) !== 'student') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $_SESSION['user'] ?? null;
if (!is_array($user) || !isset($user['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthenticated'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json'], JSON_UNESCAPED_UNICODE);
    exit;
}

$event = isset($payload['event']) ? (string) $payload['event'] : '';
$allowedEvents = ['presence', 'menu_vote', 'feedback'];
if (!in_array($event, $allowedEvents, true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'invalid_event'], JSON_UNESCAPED_UNICODE);
    exit;
}

$count = isset($payload['count']) ? (int) $payload['count'] : 1;
if ($count < 1) {
    $count = 1;
}
if ($count > 20) {
    $count = 20;
}

$all = readParticipationData($storageFile);
$found = false;
$currentUser = (string) $user['user'];
$currentName = isset($user['name']) ? (string) $user['name'] : $currentUser;
$now = date('Y-m-d H:i:s');
$weekKey = currentWeekKey();

foreach ($all as &$entry) {
    $entryUser = isset($entry['user']) ? (string) $entry['user'] : '';
    if ($entryUser !== $currentUser) {
        continue;
    }

    $entry['name'] = $currentName;
    if (!isset($entry['weeks']) || !is_array($entry['weeks'])) {
        $entry['weeks'] = [];
    }

    $weekStats = getEntryWeekStats($entry, $weekKey);
    $weekStats[$event] += $count;
    $weekStats['total'] = $weekStats['presence'] + $weekStats['menu_vote'] + $weekStats['feedback'];

    $entry['weeks'][$weekKey] = $weekStats;
    $entry['presence'] = $weekStats['presence'];
    $entry['menu_vote'] = $weekStats['menu_vote'];
    $entry['feedback'] = $weekStats['feedback'];
    $entry['total'] = $weekStats['total'];
    $entry['weekKey'] = $weekKey;
    $entry['lastActivity'] = $now;
    $found = true;
    break;
}
unset($entry);

if (!$found) {
    $weekStats = [
        'presence' => 0,
        'menu_vote' => 0,
        'feedback' => 0,
        'total' => 0
    ];
    $weekStats[$event] = $count;
    $weekStats['total'] = $weekStats['presence'] + $weekStats['menu_vote'] + $weekStats['feedback'];

    $newEntry = [
        'user' => $currentUser,
        'name' => $currentName,
        'presence' => $weekStats['presence'],
        'menu_vote' => $weekStats['menu_vote'],
        'feedback' => $weekStats['feedback'],
        'total' => $weekStats['total'],
        'weekKey' => $weekKey,
        'weeks' => [
            $weekKey => $weekStats
        ],
        'lastActivity' => $now
    ];
    $all[] = $newEntry;
}

$ok = writeParticipationData($storageFile, $all);
if ($ok === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'write_failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
