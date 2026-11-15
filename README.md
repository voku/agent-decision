# agent-decision

A small, **provider-neutral PHP 8.3+** library that returns bounded decision recommendations without taking authority over a workflow.

**State: experimental, not released.** Extracted from [agent-loop PR #1044](https://github.com/voku/agent-loop/pull/1044) and [issue #646](https://github.com/voku/agent-loop/issues/646). Neither localhost tests nor model confidence demonstrate improved engineering decisions.

## Responsibility

- `DecisionModel` is a choice-provider interface. The included `SystemOneChoiceHttpClient` is **one optional protocol adapter**, not a dependency of the domain model.
- `DecisionRequest` contains caller-approved canonical options, a deterministic baseline, explicit public-data classification, and options that **cannot be positively recommended from public metadata**.
- `BoundedDecisionAdvisor` validates provider output independently, supports `off`, `rules`, `shadow`, and `assist`, preserves the baseline on provider failure, and never approves or mutates anything.
- `DecisionAdvice` is an immutable receipt with `authority=advisory_only` and `lifecycle_mutation=false`.

**What does not belong here:** Task Contracts, workflow approvals, agent role discovery, GitHub/Codex/Claude-specific behavior, agent-loop CLI entry points, Learning persistence, final human choices, or private project state.

## Minimal example

```php
<?php

declare(strict_types=1);

use voku\AgentDecision\BoundedDecisionAdvisor;
use voku\AgentDecision\DataClassification;
use voku\AgentDecision\DecisionMode;
use voku\AgentDecision\DecisionRequest;

$request = new DecisionRequest(
    criteria: [
        'investigator' => 'Find the relevant owner and evidence.',
        'reviewer' => 'Independently review the current evidence.',
    ],
    publicState: '{"task_type":"public_issue","candidate_count":2}',
    classification: DataClassification::Public,
    baselineChoice: null,
);

// Default is OFF; no network or provider is configured.
$advice = (new BoundedDecisionAdvisor())->advise($request, DecisionMode::Off);
var_export($advice->toArray());
```

Remote modes require an explicitly injected `DecisionModel` and a caller assertion of `DataClassification::Public`. This enum is **not** an automatic privacy filter. Never pass private code, customer details, credentials, paths, source diffs, emails, or Recall/Session payloads to a provider.

For approval-like questions, mark positive actions with `choicesRequiringEvidence`; models that only see structural public metadata cannot recommend those actions. Final authority stays in the invoking application and named human.

## Verification

```bash
composer install
composer ci
```

GitHub Actions additionally tests the real HTTP adapter against a deterministic local server for success, abstention, malformed responses, oversized responses, HTTP failures, and timeout, across PHP 8.3–8.5. No paid provider key is required.

See [architecture](docs/architecture.md) and [experiment protocol](docs/experiments.md). No real-provider quality or economic advantage is claimed.
