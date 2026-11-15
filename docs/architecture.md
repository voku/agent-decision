# Architecture and boundaries

The separation is intentional:

```text
agent-loop (caller)
  -> discovers authorised options and deterministic baseline
  -> classifies outgoing state as public (or does NOT transmit)
  -> chooses protected positive actions and final human gate
  -> agent-decision (pure advisory receipt)
       -> optional DecisionModel adapter
       -> validated suggestion / abstain / fallback
  -> caller keeps final selection and all lifecycle authority
```

`agent-decision` must **not** import `voku/agent-loop`, `agent-ui`, agents/hosts, or private task context. The local provider is one adapter. Do not rename shared concepts after a vendor.

Modes: `off` (no remote call), `rules` (caller baseline only), `shadow` (observe but do not override), `assist` (allow bounded non-authoritative hint).

Caller requirements: canonical choices; explicit public-data classification; evidence-protected positive actions (approve, acknowledge, accept risk, supersede, persist Learning); human approval when required. Provider output is untrusted. Unknown IDs, stale sets, non-finite and out-of-range probabilities cannot become hints. Timeouts or errors retain the caller's baseline.

An explicitly configured URL is a **privileged operator setting**, not an arbitrary value from end users or model output. The HTTP adapter requires HTTPS except opt-in numeric loopback for deterministic tests. An operator must separately restrict outbound networking: this transport does not implement a complete DNS-rebinding or egress policy.

Normal CI has no provider secret and never relies on paid API responses. A future live provider experiment must execute only trusted main-branch code with an explicitly approved GitHub Environment, public fixtures, and sanitized receipts.

The extracted package is experimental until real paired/crossover runs show an observable benefit and a stable consumer release exists. No automatic release, version claim, approval, or merge is implied.
