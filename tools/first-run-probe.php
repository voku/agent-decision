<?php

declare(strict_types=1);

use voku\AgentDecision\BoundedDecisionAdvisor;
use voku\AgentDecision\DataClassification;
use voku\AgentDecision\DecisionDisposition;
use voku\AgentDecision\DecisionMode;
use voku\AgentDecision\DecisionRequest;
use voku\AgentDecision\Provider\SystemOneChoiceHttpClient;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Operator-controlled connectivity probe, not an engineering-outcome experiment.
 * Run only with reviewed, explicitly public fixtures in experiments/public-fixtures.
 */
try {
    if (!isset($argv)) {
        throw new InvalidArgumentException('This probe requires PHP CLI arguments.');
    }
    $modeName = 'rules';
    $fixtureName = 'experiments/public-fixtures/specialist-routing.json';
    $publicApproved = false;
    $allowLoopback = false;

    foreach (array_slice($argv, 1) as $argument) {
        if (str_starts_with($argument, '--mode=')) {
            $modeName = substr($argument, 7);
        } elseif (str_starts_with($argument, '--fixture=')) {
            $fixtureName = substr($argument, 10);
        } elseif ($argument === '--public-approved') {
            $publicApproved = true;
        } elseif ($argument === '--allow-loopback') {
            $allowLoopback = true;
        } else {
            throw new InvalidArgumentException('Unknown argument. Use --mode, --fixture, --public-approved, or --allow-loopback.');
        }
    }

    if (!in_array($modeName, ['off', 'rules', 'shadow', 'assist'], true)) {
        throw new InvalidArgumentException('Invalid mode: expected off, rules, shadow, or assist.');
    }
    $mode = DecisionMode::from($modeName);
    $remote = $mode === DecisionMode::Shadow || $mode === DecisionMode::Assist;
    if ($remote && !$publicApproved) {
        throw new InvalidArgumentException('Remote probing requires explicit --public-approved operator consent.');
    }

    $allowedDirectory = realpath(dirname(__DIR__) . '/experiments/public-fixtures');
    $fixtureFile = realpath(dirname(__DIR__) . '/' . $fixtureName);
    if (
        $allowedDirectory === false
        || $fixtureFile === false
        || !str_starts_with($fixtureFile, $allowedDirectory . DIRECTORY_SEPARATOR)
        || !is_file($fixtureFile)
    ) {
        throw new InvalidArgumentException('Fixture must be a file inside experiments/public-fixtures.');
    }
    $raw = file_get_contents($fixtureFile);
    if (!is_string($raw) || $raw === '' || strlen($raw) > 8192) {
        throw new InvalidArgumentException('Public fixture must contain 1..8192 bytes.');
    }
    $fixture = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    if (
        !is_array($fixture)
        || ($fixture['schema_version'] ?? null) !== '1.0'
        || ($fixture['classification'] ?? null) !== 'public'
        || !is_string($fixture['id'] ?? null)
        || preg_match('/\A[a-z][a-z0-9._-]{0,79}\z/', $fixture['id']) !== 1
        || !is_array($fixture['criteria'] ?? null)
        || !is_string($fixture['public_state'] ?? null)
        || !array_key_exists('baseline_choice', $fixture)
        || ($fixture['baseline_choice'] !== null && !is_string($fixture['baseline_choice']))
        || !is_array($fixture['choices_requiring_evidence'] ?? null)
    ) {
        throw new InvalidArgumentException('Fixture schema or public classification is invalid.');
    }

    $protectedChoices = $fixture['choices_requiring_evidence'];
    if (!array_is_list($protectedChoices)) {
        throw new InvalidArgumentException('Evidence-protected choices must be a list.');
    }
    foreach ($protectedChoices as $choice) {
        if (!is_string($choice) || $choice === '') {
            throw new InvalidArgumentException('Evidence-protected choices must be non-empty strings.');
        }
    }
    /** @var list<non-empty-string> $protectedChoices */

    $request = new DecisionRequest(
        criteria: $fixture['criteria'],
        publicState: $fixture['public_state'],
        classification: DataClassification::Public,
        baselineChoice: $fixture['baseline_choice'],
        choicesRequiringEvidence: $protectedChoices,
        model: (($model = getenv('AGENT_DECISION_PROVIDER_MODEL')) !== false && $model !== '') ? $model : null,
    );

    $provider = null;
    if ($remote) {
        $endpoint = getenv('AGENT_DECISION_PROVIDER_ENDPOINT');
        if (!is_string($endpoint) || $endpoint === '') {
            throw new InvalidArgumentException('AGENT_DECISION_PROVIDER_ENDPOINT is required for remote modes.');
        }
        $timeoutValue = getenv('AGENT_DECISION_PROVIDER_TIMEOUT_MS');
        $timeout = $timeoutValue === false || $timeoutValue === '' ? 3000 : $timeoutValue;
        if (!ctype_digit((string) $timeout)) {
            throw new InvalidArgumentException('Provider timeout must be an integer in milliseconds.');
        }
        $credential = getenv('AGENT_DECISION_PROVIDER_TOKEN');
        $provider = new SystemOneChoiceHttpClient(
            $endpoint,
            (int) $timeout,
            $allowLoopback,
            is_string($credential) && $credential !== '' ? $credential : null,
        );
    }

    $startedAt = hrtime(true);
    $advice = (new BoundedDecisionAdvisor($provider))->advise($request, $mode);
    $latencyMs = round((hrtime(true) - $startedAt) / 1_000_000, 3);
    $providerReplied = $advice->providerError === null && in_array(
        $advice->providerDisposition,
        [DecisionDisposition::Suggest, DecisionDisposition::Abstain],
        true,
    );
    $status = !$remote ? 'offline_only' : ($providerReplied ? 'provider_replied' : 'provider_failed');

    $receipt = [
        'schema_version' => '1.0',
        'kind' => 'decision_connectivity_probe',
        'recorded_at_utc' => gmdate('Y-m-d\TH:i:s\Z'),
        'experiment_id' => $fixture['id'],
        'fixture_sha256' => 'sha256:' . hash('sha256', $raw),
        'classification' => 'operator_approved_public',
        'preflight_status' => $status,
        'latency_ms' => $latencyMs,
        'decision_advice' => $advice->toArray(),
        'engineering_outcome_observed' => false,
    ];
    echo json_encode($receipt, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

    exit($status === 'provider_failed' ? 2 : 0);
} catch (InvalidArgumentException | JsonException $exception) {
    fwrite(STDERR, '[FAIL] decision probe preflight: ' . $exception->getMessage() . "\n");
    exit(1);
} catch (Throwable) {
    // No endpoint, credential, source payload or provider response is logged.
    fwrite(STDERR, "[FAIL] decision probe unexpectedly failed; no provider details were logged.\n");
    exit(1);
}
