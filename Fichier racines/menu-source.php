<?php
header('Content-Type: application/json; charset=utf-8');

$localMenuFile = __DIR__ . '/menu-week.json';
$configFile = __DIR__ . '/menu-source-config.json';
$requestedWeek = isset($_GET['week']) ? trim((string) $_GET['week']) : 'next';
if ($requestedWeek !== 'current' && $requestedWeek !== 'next') {
    $requestedWeek = 'next';
}

function readJsonFile($path) {
    if (!file_exists($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function isValidMenuPayload($payload) {
    if (!is_array($payload)) {
        return false;
    }

    if (!isset($payload['dishes']) || !is_array($payload['dishes'])) {
        return false;
    }

    return true;
}

function normalizeText($value) {
    $decoded = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $singleLine = preg_replace('/\s+/u', ' ', $decoded);
    return trim((string) $singleLine);
}

function extractSemaineTokens($html) {
    $tokens = [];
    if (preg_match_all('/name="semaine"\s+value="?([0-9]{9,12})"?/i', (string) $html, $matches)) {
        foreach ($matches[1] as $token) {
            $tokens[] = (int) $token;
        }
    }

    $tokens = array_values(array_unique($tokens));
    sort($tokens);
    return $tokens;
}

function buildUrlWithSemaineToken($baseUrl, $token) {
    $parts = parse_url($baseUrl);
    if (!is_array($parts) || !isset($parts['scheme']) || !isset($parts['host'])) {
        return null;
    }

    $query = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['semaine'] = (string) $token;

    $path = isset($parts['path']) ? $parts['path'] : '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    return $parts['scheme'] . '://' . $parts['host'] . $port . $path . '?' . http_build_query($query) . $fragment;
}

function fetchNextWeekHtml($currentHtml, $remoteUrl, $context) {
    $tokens = extractSemaineTokens($currentHtml);
    if (count($tokens) < 2) {
        return null;
    }

    $nextToken = max($tokens);
    $nextUrl = buildUrlWithSemaineToken($remoteUrl, $nextToken);
    if ($nextUrl === null) {
        return null;
    }

    $nextHtml = @file_get_contents($nextUrl, false, $context);
    if ($nextHtml === false || trim((string) $nextHtml) === '') {
        return null;
    }

    return $nextHtml;
}

function inferDishTypeFromRowId($rowId, $slot) {
    $normalizedRow = mb_strtolower((string) $rowId, 'UTF-8');
    $slotNum = (int) $slot;

    if (str_contains($normalizedRow, '_dejeuner_') && $slotNum === 3) {
        return 'vege';
    }

    if (str_contains($normalizedRow, '_diner_') && $slotNum === 2) {
        return 'vege';
    }

    return 'non-vege';
}

function inferDishMealFromRowId($rowId) {
    $normalizedRow = mb_strtolower((string) $rowId, 'UTF-8');
    if (str_contains($normalizedRow, '_dejeuner_')) {
        return 'midi';
    }

    if (str_contains($normalizedRow, '_diner_')) {
        return 'soir';
    }

    return 'unknown';
}

function inferDishDayFromRowId($rowId) {
    $normalizedRow = mb_strtolower((string) $rowId, 'UTF-8');
    $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
    foreach ($days as $day) {
        if (str_contains($normalizedRow, '_' . $day . '_')) {
            return $day;
        }
    }

    return 'unknown';
}

function buildMenuPayloadFromHtml($html, $defaultWeekLabel) {
    $weekLabel = $defaultWeekLabel;
    if (preg_match('/Semaine\s+([0-9]{1,2})/iu', $html, $weekMatch)) {
        $weekLabel = 'Semaine ' . $weekMatch[1];
    }

    $dishCandidates = [];

    if (preg_match_all('/<a\s+href=#([^\s>]+)[^>]*>\s*<font[^>]*>\s*Plat\s*([0-9]+)\s*<\/font>\s*<\/a>.*?<td[^>]*>\s*<a[^>]*>([^<]+)<\/a>/isu', $html, $matchesHtml, PREG_SET_ORDER)) {
        foreach ($matchesHtml as $match) {
            $dishCandidates[] = [
                'name' => $match[3],
                'type' => inferDishTypeFromRowId($match[1], $match[2]),
                'meal' => inferDishMealFromRowId($match[1]),
                'day' => inferDishDayFromRowId($match[1])
            ];
        }
    }

    if (preg_match_all('/Plat\s*[0-9]+\s*\|\s*([^\n\r\|]+)/iu', $html, $matchesText)) {
        foreach ($matchesText[1] as $candidateName) {
            $dishCandidates[] = [
                'name' => $candidateName,
                'type' => 'non-vege',
                'meal' => 'unknown',
                'day' => 'unknown'
            ];
        }
    }

    $unique = [];
    foreach ($dishCandidates as $candidate) {
        $rawName = is_array($candidate) && isset($candidate['name']) ? $candidate['name'] : '';
        $dishType = is_array($candidate) && isset($candidate['type']) ? (string) $candidate['type'] : 'non-vege';
        $dishMeal = is_array($candidate) && isset($candidate['meal']) ? (string) $candidate['meal'] : 'unknown';
        $dishDay = is_array($candidate) && isset($candidate['day']) ? (string) $candidate['day'] : 'unknown';

        $name = normalizeText(strip_tags((string) $rawName));
        if ($name === '') {
            continue;
        }

        $lower = mb_strtolower($name, 'UTF-8');
        if (str_contains($lower, 'ferme')) {
            continue;
        }

        $normalizedName = preg_replace('/\s+/u', ' ', $lower);
        $normalizedMeal = ($dishMeal === 'midi' || $dishMeal === 'soir') ? $dishMeal : 'unknown';
        $normalizedDay = in_array($dishDay, ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'], true) ? $dishDay : 'unknown';
        $key = $normalizedDay . '|' . $normalizedMeal . '|' . $normalizedName;
        if (!isset($unique[$key])) {
            $unique[$key] = [
                'emoji' => '🍽️',
                'name' => $name,
                'desc' => '',
                'type' => $dishType === 'vege' ? 'vege' : 'non-vege',
                'meal' => $normalizedMeal,
                'day' => $normalizedDay
            ];
        }
    }

    return [
        'weekLabel' => $weekLabel,
        'dishes' => array_values($unique)
    ];
}

$localPayload = readJsonFile($localMenuFile);
if (!isValidMenuPayload($localPayload)) {
    $localPayload = [
        'weekLabel' => 'Semaine en cours',
        'dishes' => []
    ];
}

$config = readJsonFile($configFile);
$remoteUrl = '';
if (is_array($config) && isset($config['remoteMenuUrl'])) {
    $remoteUrl = trim((string) $config['remoteMenuUrl']);
}

if ($remoteUrl === '') {
    echo json_encode($localPayload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($remoteUrl, FILTER_VALIDATE_URL)) {
    echo json_encode($localPayload, JSON_UNESCAPED_UNICODE);
    exit;
}

$scheme = (string) parse_url($remoteUrl, PHP_URL_SCHEME);
if ($scheme !== 'http' && $scheme !== 'https') {
    echo json_encode($localPayload, JSON_UNESCAPED_UNICODE);
    exit;
}

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'header' => "Accept: application/json\r\nUser-Agent: RAK-Menu-Source/1.0\r\n"
    ]
]);

$remoteRaw = @file_get_contents($remoteUrl, false, $context);
if ($remoteRaw === false || trim($remoteRaw) === '') {
    echo json_encode($localPayload, JSON_UNESCAPED_UNICODE);
    exit;
}

$remotePayload = json_decode($remoteRaw, true);
if (isValidMenuPayload($remotePayload)) {
    echo json_encode($remotePayload, JSON_UNESCAPED_UNICODE);
    exit;
}

$htmlToParse = $remoteRaw;
if ($requestedWeek === 'next') {
    $nextWeekHtml = fetchNextWeekHtml($remoteRaw, $remoteUrl, $context);
    if (is_string($nextWeekHtml) && trim($nextWeekHtml) !== '') {
        $htmlToParse = $nextWeekHtml;
    }
}

$htmlPayload = buildMenuPayloadFromHtml($htmlToParse, (string) $localPayload['weekLabel']);
if (isValidMenuPayload($htmlPayload) && count($htmlPayload['dishes']) > 0) {
    echo json_encode($htmlPayload, JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($localPayload, JSON_UNESCAPED_UNICODE);
