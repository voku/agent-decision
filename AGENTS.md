# Repository agent guidance

This package is an experimental **advisory bounded-choice library**, not a workflow controller.

- Keep the public API host-neutral and provider-neutral. CLI lifecycle, Task Contracts, human approval, skill discovery, and Learning persistence stay in consumer repositories.
- Default to offline behavior. Never silently enable a paid provider or discover credentials from the environment.
- Only transmit caller-explicitly-classified public data. This library does not sanitize private context or provide a complete network egress policy.
- Treat every provider response as untrusted: verify canonical option IDs, abstention consistency, and finite probabilities between 0 and 1.
- Public metadata cannot justify positive approval, risk acceptance, review acknowledgement, or durable Learning decisions. These remain with the caller and named human.
- Keep code strict-typed, immutable, minimal, and analyzable at PHPStan max. Use PHPUnit, PHP-CS-Fixer, and the complete exact-head PHP 8.3–8.5 GitHub Actions matrix.
- Test the real transport with loopback fixtures, not a production secret. Synthetic success proves contracts, **not** the quality or value of a real decision model.
- Follow `docs/experiments.md` for matched, preregistered, observable A/B evidence before arguing for a stable release.
- Do not auto-approve, merge, publish, bypass review, or manufacture outcome evidence.
