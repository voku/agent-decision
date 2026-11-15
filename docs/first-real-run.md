# First real decision experiment: operator runbook

This runbook separates **provider connectivity** from **observed agent behavior**. A successful probe does **not** establish a better specialist choice, fewer false leads, lower cost, or approval authority.

## 1. Preflight without any provider

From a fresh checkout of a reviewed commit of `voku/agent-decision`:

~~~bash
composer install --no-interaction
composer ci
php tools/first-run-probe.php --mode=rules > rules.json
~~~

Check the single JSON receipt: `preflight_status=offline_only`, `engineering_outcome_observed=false`, advisory authority and no lifecycle mutation. Do not commit potentially sensitive receipts.

## 2. Explicit operator approval of one public fixture

Review `experiments/public-fixtures/specialist-routing.json`, including the complete `public_state` and every candidate description. It is synthetic public metadata, **not** a private work task. `classification=public` is an operator assertion, not a redaction system. Never put task Contracts, internal source, paths, credentials, customer data, emails, or private Recall/Session context in a fixture.

Use a provider endpoint that accepts `POST /v1/systemone`, the bounded choice protocol (including the `none` abstention option and the complete probability map). The adapter requires HTTPS, except explicitly permitted numeric `127.0.0.1` for local tests. Corporate certificate roots must be configured correctly; do not disable TLS verification. An operator must enforce DNS/egress restrictions because this adapter cannot guarantee them.

Set environment variables in the **trusted local process** (never in the repo, task file, CLI argument, or CI logs):

~~~bash
export AGENT_DECISION_PROVIDER_ENDPOINT='https://approved-provider.example/v1/systemone'
# Supply AGENT_DECISION_PROVIDER_TOKEN via a trusted secret store if required.
# Optionally: AGENT_DECISION_PROVIDER_MODEL, AGENT_DECISION_PROVIDER_TIMEOUT_MS (100..10000).
php tools/first-run-probe.php --mode=shadow --public-approved > shadow.json
php tools/first-run-probe.php --mode=assist --public-approved > assist.json
~~~

Do **not** use `--allow-loopback` for the real provider. Exit code `1` means preflight/configuration failed. Exit code `2` means the provider failed or returned an unusable choice, even if the library preserved the baseline. A zero exit code in `shadow`/`assist` means only that the provider returned valid bounded advice or abstained. Neither mode runs an agent or proves a useful decision.

Compare `fixture_sha256`, `provider_disposition`, `provider_observed_choice`, `suggested_choice`, and `latency_ms`. Both remote runs must refer to the exact same fixture hash. `shadow` must keep the baseline; `assist` may return a different **advisory-only** choice. Probe output intentionally excludes the raw fixture, URL and credentials. Preserve only reviewed, sanitized receipts.

## 3. Actual matched agent run (separate step)

First preregister two or more representative **public** tasks and immutable repository SHAs in a trial note, together with the exact allowed specialist IDs, acceptance criteria, host/model versions, tool availability, negative cases and stop rule. Use the same environment and fresh isolated sessions. For each task, run arm **A** without any hint, and arm **B** with one frozen, matching, approved `assist` hint; reverse A/B order for another task. Never expose arm B's hint to arm A. Do not silently approve a Contract, review, Learning outcome or risk acceptance.

**Record observed behavior**, not the advice JSON alone: actual first selected specialist, first source-inspection command, first relevant code owner, validation selected, false leads, correctness evidence, elapsed time and tokens/cost **only if genuinely recorded**. Mark missing metrics `NOT_OBSERVED`. Keep the agent-loop `enter`/`finish` and decision-required gates authoritative. This library owns neither lifecycle nor specialist discovery.

Compare outcomes with the decision criteria in [`docs/experiments.md`](experiments.md). Repeat beyond the first two tasks before considering production promotion. If advice makes no measurable difference, disable or remove the provider rather than multiplying calls.

## 4. Scope and safety gates

- No production secret in GitHub PR workflows or untrusted code. A live run requires the authorized operator and trusted network, not a public CI worker by default.
- A provider timeout, transport error or invalid response must be visible as `provider_failed` and exit `2`; this is **not** a passing live probe.
- Provider choice cannot change workflow authority or mutate state. A probable suggestion is not approval.
- Do not publish a stable tag, merge `agent-loop#1044`, or claim cost/quality benefits from a localhost fake. Those require separate review and evidence.
