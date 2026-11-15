<?php

declare(strict_types=1);

if (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) !== '/v1/systemone') {
    http_response_code(404);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}
if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Bearer local-test-token-only') {
    http_response_code(401);
    exit;
}
if (!str_starts_with((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    http_response_code(415);
    exit;
}

$raw = file_get_contents('php://input');
$request = is_string($raw) ? json_decode($raw, true) : null;
$criteria = $request['questions']['decision']['criteria'] ?? null;
$state = $request['state'] ?? null;
if (
    !is_array($criteria)
    || !is_string($state)
    || ($request['questions']['decision']['type'] ?? null) !== 'choice'
    || !isset($criteria['none'])
) {
    http_response_code(400);
    exit;
}
if (str_starts_with($state, 'HTTP_ERROR')) {
    http_response_code(503);
    exit;
}
if (str_starts_with($state, 'TIMEOUT')) {
    usleep(1_500_000);
}
header('Content-Type: application/json');
if (str_starts_with($state, 'SLOW_TRICKLE')) {
    header('Cache-Control: no-cache');
    for ($i = 0; $i < 15; ++$i) {
        echo 'x';
        flush();
        usleep(100_000);
    }
    exit;
}
if (str_starts_with($state, 'OVERSIZED')) {
    echo str_repeat('X', 65536);
    exit;
}
if (str_starts_with($state, 'INVALID')) {
    echo '{"answers":{"decision":{"type":"choice","choice":"invented","probabilities":{}}}}';
    exit;
}
if (str_starts_with($state, 'OUT_OF_RANGE')) {
    $probabilities = array_fill_keys(array_keys($criteria), 0.1);
    $probabilities['investigator'] = 1.2;
    echo json_encode(['answers' => ['decision' => ['type' => 'choice', 'choice' => 'investigator', 'probabilities' => $probabilities]]], JSON_THROW_ON_ERROR);
    exit;
}

$choice = str_starts_with($state, 'ABSTAIN') ? 'none' : 'investigator';
if (!isset($criteria[$choice])) {
    http_response_code(400);
    exit;
}
$probabilities = [];
foreach ($criteria as $id => $description) {
    $probabilities[$id] = $id === $choice ? 0.8 : 0.2 / (count($criteria) - 1);
}
echo json_encode([
    'answers' => ['decision' => [
        'type' => 'choice',
        'choice' => $choice,
        'probabilities' => $probabilities,
        'confidence' => 0.6,
    ]],
], JSON_THROW_ON_ERROR);
