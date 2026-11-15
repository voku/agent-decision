<?php

declare(strict_types=1);

namespace voku\AgentDecision;

final readonly class DecisionChoiceResult
{
    public function __construct(
        public ?string $candidateId,
        public ?float $answerProbability,
    ) {
    }
}
