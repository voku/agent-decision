<?php

declare(strict_types=1);

namespace voku\AgentDecision;

/**
 * Advisory receipt only. There is no method for persisting, approving or
 * authorizing the caller's workflow state.
 */
final readonly class DecisionAdvice
{
    public function __construct(
        public DecisionMode $mode,
        public DecisionDisposition $disposition,
        public ?string $suggestedChoice,
        public string $source,
        public DecisionDisposition $providerDisposition,
        public ?string $providerObservedChoice = null,
        public ?float $providerProbability = null,
        public ?string $providerError = null,
    ) {
    }

    /**
     * @return array{
     *     schema_version: '1.0',
     *     authority: 'advisory_only',
     *     lifecycle_mutation: false,
     *     mode: string,
     *     disposition: string,
     *     suggested_choice: string|null,
     *     source: string,
     *     provider_disposition: string,
     *     provider_observed_choice: string|null,
     *     provider_probability: float|null,
     *     provider_error: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'schema_version' => '1.0',
            'authority' => 'advisory_only',
            'lifecycle_mutation' => false,
            'mode' => $this->mode->value,
            'disposition' => $this->disposition->value,
            'suggested_choice' => $this->suggestedChoice,
            'source' => $this->source,
            'provider_disposition' => $this->providerDisposition->value,
            'provider_observed_choice' => $this->providerObservedChoice,
            'provider_probability' => $this->providerProbability,
            'provider_error' => $this->providerError,
        ];
    }
}
