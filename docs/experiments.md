# Preregistered decision-impact experiment

Proven so far: PHP contract / option and probability validation, deterministic localhost wire protocol, offline fallbacks, and human-authority guard. **Not proven:** a better first specialist choice, fewer false leads, reduced tokens/time, calibrated probabilities, or a cost advantage over the default host model.

Do not treat fixture success, a pretty probability, or a green GitHub workflow as outcome evidence.

## Minimal paired/crossover trial

For at least two representative **public** engineering tasks, fix the same exact base SHA, task description, managed specialist candidates, tools, runtime/model, and acceptance criteria. Run A (existing workflow, no hint) and B (same conditions with one frozen decision hint), using fresh isolated sessions. Reverse the A/B order across tasks. Do not expose the B recommendation to A. Neither arm may silently approve a Contract or alter workflow authority.

Preregister before running:

- Task IDs and exact baseline SHAs.
- Public input and allowed candidate IDs (record hashes and approved redacted fixture).
- What constitutes a better *observable* first decision, including a falsifiable negative case.
- Which host/model version is running and how order/tool availability are controlled.
- Stop rule and attribution limits.

Record for each arm: actual first chosen specialist, first source-inspection command, first relevant behavior owner, selected validation, false leads, elapsed wall time, host-reported token counts **if genuinely available**, observed error/timeout, and follow-on correctness evidence. Store non-secret immutable receipts. Record missing metrics as **NOT_OBSERVED**, never as zero or fabricated estimates.

`shadow` results cannot claim behavioral impact because they are not shown to the acting host. `assist` tests must observe an actual changed decision, not just an emitted JSON field. Repeat beyond two tasks before attempting a production promotion.

## Decision rule

Promote only if repeated matched trials show a meaningful improved decision, with bounded latency/cost and no regression in safety, correctness or privacy. Otherwise retain this as an experiment or remove the optional provider. Never choose a confidence cutoff without external calibration.

Optional live provider runs are non-required and require deliberate operator approval, separate trusted-code execution, public-only fixtures, no leaking secrets in logs/artifacts, and no untrusted PR-head checkout in a secret-bearing job.
