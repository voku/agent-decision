<?php

declare(strict_types=1);

/**
 * @param array<string, mixed> $probe
 * @return array<string, mixed>
 */
function advice(array $probe): array
{
    $value = $probe['decision_advice'] ?? null;
    if (!is_array($value) || array_is_list($value)) {
        throw new RuntimeException('Probe receipt is missing its structured decision advice.');
    }
    /** @var array<string, mixed> $value */

    return $value;
}

/** @return array<string, mixed> */
function receipt(string $path): array
{
    $raw = file_get_contents($path);
    if (!is_string($raw) || str_contains($raw, 'local-test-token-only') || str_contains($raw, 'ROUTE public issue:')) {
        throw new RuntimeException('Probe receipt is absent or contains provider credentials / raw task data.');
    }
    $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($decoded) || array_is_list($decoded)) {
        throw new RuntimeException('Probe receipt must be a JSON object.');
    }
    /** @var array<string, mixed> $decoded */
    $decisionAdvice = advice($decoded);
    if (
        ($decoded['kind'] ?? null) !== 'decision_connectivity_probe'
        || ($decoded['engineering_outcome_observed'] ?? null) !== false
        || ($decisionAdvice['authority'] ?? null) !== 'advisory_only'
        || ($decisionAdvice['lifecycle_mutation'] ?? null) !== false
    ) {
        throw new RuntimeException('Probe receipt lost its advisory and outcome boundaries.');
    }

    return $decoded;
}

if (!isset($argv)) {
    throw new InvalidArgumentException('This verifier requires PHP CLI arguments.');
}

if (count($argv) !== 4) {
    throw new InvalidArgumentException('Expected rules, shadow and assist receipt files.');
}

$rules = receipt($argv[1]);
$shadow = receipt($argv[2]);
$assist = receipt($argv[3]);
$rulesAdvice = advice($rules);
$shadowAdvice = advice($shadow);
$assistAdvice = advice($assist);
if (
    ($rules['preflight_status'] ?? null) !== 'offline_only'
    || ($rules['classification'] ?? null) !== 'public_fixture'
    || ($rulesAdvice['suggested_choice'] ?? null) !== 'reviewer'
    || ($rulesAdvice['provider_disposition'] ?? null) !== 'not_enabled'
    || ($shadow['preflight_status'] ?? null) !== 'provider_replied'
    || ($shadow['classification'] ?? null) !== 'operator_approved_public'
    || ($shadowAdvice['suggested_choice'] ?? null) !== 'reviewer'
    || ($shadowAdvice['provider_observed_choice'] ?? null) !== 'investigator'
    || ($assist['preflight_status'] ?? null) !== 'provider_replied'
    || ($assist['classification'] ?? null) !== 'operator_approved_public'
    || ($assistAdvice['suggested_choice'] ?? null) !== 'investigator'
    || ($assistAdvice['provider_probability'] ?? null) !== 0.8
    || ($rules['fixture_sha256'] ?? null) !== ($assist['fixture_sha256'] ?? null)
    || ($shadow['fixture_sha256'] ?? null) !== ($assist['fixture_sha256'] ?? null)
) {
    throw new RuntimeException('First-run probe failed rules/shadow/assist isolation or selected a stale fixture.');
}

echo "PASS: offline, shadow and assist receipts stay bounded, fail visibly and do not claim engineering impact\n";
