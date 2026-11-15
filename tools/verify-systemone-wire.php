<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use voku\AgentDecision\Provider\SystemOneChoiceHttpClient;

$endpoint = getenv('AGENT_DECISION_TEST_ENDPOINT');
if (!is_string($endpoint) || $endpoint === '') {
    throw new RuntimeException('AGENT_DECISION_TEST_ENDPOINT must point to a local fixture server.');
}

$criteria = ['investigator' => 'Investigate a public issue', 'reviewer' => 'Review evidence'];
$client = new SystemOneChoiceHttpClient($endpoint, 1000, true, 'local-test-token-only');

$yes = $client->decide('ROUTE public issue', $criteria, null);
if ($yes->candidateId !== 'investigator' || $yes->answerProbability !== 0.8) {
    throw new RuntimeException('Expected canonical investigator decision.');
}
$no = $client->decide('ABSTAIN insufficient evidence', $criteria, null);
if ($no->candidateId !== null || $no->answerProbability !== null) {
    throw new RuntimeException('Expected explicit abstention.');
}

foreach (['INVALID', 'OUT_OF_RANGE', 'OVERSIZED'] as $state) {
    try {
        $client->decide($state, $criteria, null);
        throw new RuntimeException('Invalid fixture accepted: ' . $state);
    } catch (UnexpectedValueException $exception) {
        if ($state === 'OVERSIZED' && $exception->getMessage() !== 'decision model provider response exceeded size limit.') {
            throw new RuntimeException('Response size limit must be enforced during retrieval.', 0, $exception);
        }
    }
}
try {
    $client->decide('HTTP_ERROR', $criteria, null);
    throw new RuntimeException('Upstream HTTP 503 was accepted.');
} catch (RuntimeException $exception) {
    if ($exception->getMessage() === 'Upstream HTTP 503 was accepted.') {
        throw $exception;
    }
}

// A deliberate timeout is run last because PHP's built-in server is single-threaded.
$short = new SystemOneChoiceHttpClient($endpoint, 100, true, 'local-test-token-only');
try {
    $short->decide('TIMEOUT', $criteria, null);
    throw new RuntimeException('Upstream timeout was accepted.');
} catch (RuntimeException $exception) {
    if ($exception->getMessage() === 'Upstream timeout was accepted.') {
        throw $exception;
    }
}
echo "PASS: bounded loopback request, authentication, abstention, malformed response, size limit, HTTP errors and timeout\n";
