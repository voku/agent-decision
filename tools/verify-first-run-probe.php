<?php

declare(strict_types=1);

/** @return array<string, mixed> */
function receipt(string $path): array
{
    $raw = file_get_contents($path);
    if (!is_string($raw) || str_contains($raw, 'local-test-token-only') || str_contains($raw, 'ROUTE public issue:')) {
        throw new RuntimeException('Probe receipt is absent or contains provider credentials / raw task data.');
    }
    $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException('Probe receipt must be a JSON object.');
    }
    if (
        ($decoded['kind'] ?? null) !== 'decision_connectivity_probe'
        || ($decoded['engineering_outcome_observed'] ?? null) !== false
        || ($decoded['decision_advice']['authority'] ?? null) !== 'advisory_only'
        || ($decoded['decision_advice']['lifecycle_mutation'] ?? null) !== false
    ) {
        throw new RuntimeException('Probe receipt lost its advisory and outcome boundaries.');
    }

    return $decoded;
}

if (count($argv) !== 4) {
    throw new InvalidArgumentException('Expected rules, shadow and assist receipt files.');
}

$rules = receipt($argv[1]);
$shadow = receipt($argv[2]);
$assist = receipt($argv[3]);
if (
    ($rules['preflight_status'] ?? null) !== 'offline_only'
    || ($rules['decision_advice']['suggested_choice'] ?? null) !== 'reviewer'
    || ($rules['decision_advice']['provider_disposition'] ?? null) !== 'not_enabled'
    || ($shadow['preflight_status'] ?? null) !== 'provider_replied'
    || ($shadow['decision_advice']['suggested_choice'] ?? null) !== 'reviewer'
    || ($shadow['decision_advice']['provider_observed_choice'] ?? null) !== 'investigator'
    || ($assist['preflight_status'] ?? null) !== 'provider_replied'
    || ($assist['decision_advice']['suggested_choice'] ?? null) !== 'investigator'
    || ($assist['decision_advice']['provider_probability'] ?? null) !== 0.8
    || ($rules['fixture_sha256'] ?? null) !== ($assist['fixture_sha256'] ?? null)
    || ($shadow['fixture_sha256'] ?? null) !== ($assist['fixture_sha256'] ?? null)
) {
    throw new RuntimeException('First-run probe failed rules/shadow/assist isolation or selected a stale fixture.');
}

echo "PASS: offline, shadow and assist receipts stay bounded, fail visibly and do not claim engineering impact\n";
