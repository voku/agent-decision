<?php

declare(strict_types=1);

namespace voku\AgentDecision;

use InvalidArgumentException;

/**
 * Canonical caller-owned options. Never derives options or public-data
 * classification from a provider's response.
 */
final readonly class DecisionRequest
{
    /** @var non-empty-array<non-empty-string, non-empty-string> */
    public array $criteria;

    /**
     * @param array<array-key, mixed> $criteria
     * @param list<non-empty-string> $choicesRequiringEvidence
     */
    public function __construct(
        array $criteria,
        public string $publicState = '',
        public DataClassification $classification = DataClassification::Unclassified,
        public ?string $baselineChoice = null,
        public array $choicesRequiringEvidence = [],
        public ?string $model = null,
    ) {
        if ($criteria === [] || count($criteria) > 25 || array_key_exists('none', $criteria)) {
            throw new InvalidArgumentException('Expected 1..25 canonical options excluding the reserved none choice.');
        }

        foreach ($criteria as $id => $description) {
            if (
                !is_string($id)
                || preg_match('/\A[a-z][a-z0-9._-]{0,79}\z/', $id) !== 1
                || !is_string($description)
                || trim($description) === ''
            ) {
                throw new InvalidArgumentException('Every canonical option requires a valid identifier and description.');
            }
        }

        /** @var non-empty-array<non-empty-string, non-empty-string> $criteria */
        $this->criteria = $criteria;

        if (
            strlen($publicState) > 2000
            || ($classification === DataClassification::Public && trim($publicState) === '')
        ) {
            throw new InvalidArgumentException('Explicitly classified public state must contain 1..2000 bytes.');
        }
        if ($baselineChoice !== null && !array_key_exists($baselineChoice, $criteria)) {
            throw new InvalidArgumentException('Deterministic baseline must be a canonical option.');
        }
        if (count(array_unique($choicesRequiringEvidence)) !== count($choicesRequiringEvidence)) {
            throw new InvalidArgumentException('Choices requiring evidence must be unique.');
        }
        foreach ($choicesRequiringEvidence as $choice) {
            if (!array_key_exists($choice, $criteria)) {
                throw new InvalidArgumentException('Evidence-protected choice must be canonical.');
            }
        }
        if ($model !== null && preg_match('/\A[A-Za-z0-9._-]{1,80}\z/', $model) !== 1) {
            throw new InvalidArgumentException('Invalid decision model identifier.');
        }
    }
}
