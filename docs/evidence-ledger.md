# Evidence ledger: what was actually learned

This ledger distinguishes a transport contract passing CI from a causal improvement in engineering decisions. It is not a provider benchmark.

## 2026-11-15: extracting the bounded decision core

**Owner:** [agent-decision PR #1](https://github.com/voku/agent-decision/pull/1)

**Reproducible package evidence:** At head `8c9a4049e3fcc1631f2d6d46b8ad2d64a410167a`, [Actions run 43694404893](https://github.com/voku/agent-decision/actions/runs/43694404893) completed successfully across PHP 8.3, 8.4, and 8.5.

The jobs execute PHPUnit, PHPStan max, PHP-CS-Fixer, Composer validation and a real localhost HTTP adapter. Negative cases cover unknown and malformed options, invalid probabilities, abstention, HTTP failures, response size, slow-drip body deadlines, bearer-header control bytes and oversized candidate descriptions. These are **contract and failure-safety checks**. A deterministic fake cannot establish recommendation accuracy or economic value.

**Consumer evidence:** [agent-loop PR #1044](https://github.com/voku/agent-loop/pull/1044) verifies its generic CLI and human-gate recommendation with the same localhost-compatible choice protocol. [Host-neutral proof run 43694307596](https://github.com/voku/agent-loop/actions/runs/43694307596) completed successfully for its exact recorded head. The workflow deliberately excludes private Contract goals, paths and review text from provider receipts. The invocation authority remains with the host and named human.

**Codex host experiment:** [agent-loop Codex runtime proof run 43694307449](https://github.com/voku/agent-loop/actions/runs/43694307449) used a deterministic fake model and controlled host fixture. It observed the fixture's baseline selection `agent-loop-code-reviewer` and its hinted selection `agent-loop-investigator`, with an observed child spawn. The data-integrity correction in [agent-loop commit 93e009ae](https://github.com/voku/agent-loop/commit/93e009aeec3bef9b3cb61c27f431a13ee7f535bf) ensures the hint probability in the receipt is derived from the same CLI response used in the hinted run, rather than a hard-coded `0.82`.

**Critical limitation:** That Codex fixture is a controlled stimulus/response proof, **not** an independent real-model specialist decision or a better engineering result. The outcome is not evidence of reduced time, fewer false leads, improved correctness, or calibrated confidence.

## Security and ownership lessons

- An explicit `Public` classification is a caller assertion, not a privacy scrubber.
- Repository-controlled `init.json` is not operator consent to contact a remote host or read credentials. The `agent-loop` integration now requires a separate exact endpoint/token-environment grant from its trusted launcher for automatic human-gate advice.
- Every provider result must be checked against caller-owned canonical options, finite bounded probabilities and conservative fallback semantics. Advisory signals never approve a Contract or mutate lifecycle state.
- Response-body bounds require an incremental, deadline-aware read, not a single `stream_get_contents` size check. The PHP HTTP header-opening phase retains a per-operation timeout limitation until a transport with a transaction-wide timeout is chosen.
- Reuse the actual suggestion in every receipt. Invented candidate IDs or fixed probabilities invalidate experiment integrity.
- Portable choice validation and protocol adapters belong to `agent-decision`. Managed role discovery, Contract authority, human-facing decisions and host-specific Codex fixture orchestration remain in `agent-loop`.

## Missing promotion evidence

The experiment still needs at least two preregistered, paired/crossover public tasks on immutable base SHAs with fresh real-agent sessions. Record observed specialist selection, first investigation command, first relevant source owner, validation, correctness, false leads, latency and actual token/cost metrics where available. Mark unavailable measurements as **NOT_OBSERVED**. The default/offline arm must remain independent of the hint.

A stable package release and Composer migration in `agent-loop` also require independent review and separately authorized release/merge. Green simulated CI is not approval.
