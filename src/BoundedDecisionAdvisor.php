<?php

declare(strict_types=1);

namespace voku\AgentDecision;

use Throwable;
use UnexpectedValueException;

/**
 * Host- and provider-neutral bounded advice. The caller owns authoritative
 * option discovery, evidence requirements, baseline and final human choice.
 */
final readonly class BoundedDecisionAdvisor
{
    public function __construct(private ?DecisionModel $model = null)
    {
    }

    public function advise(DecisionRequest $request, DecisionMode $mode = DecisionMode::Off): DecisionAdvice
    {
        $baseline = $request->baselineChoice;
        $baselineDisposition = $baseline === null ? DecisionDisposition::Abstain : DecisionDisposition::Suggest;

        if ($mode === DecisionMode::Off || $mode === DecisionMode::Rules) {
            return new DecisionAdvice($mode, $baselineDisposition, $baseline, 'deterministic', DecisionDisposition::NotEnabled);
        }

        if ($request->classification !== DataClassification::Public) {
            return new DecisionAdvice($mode, $baselineDisposition, $baseline, 'deterministic', DecisionDisposition::NotEnabled, providerError: 'public_classification_required');
        }

        if ($this->model === null) {
            return new DecisionAdvice($mode, $baselineDisposition, $baseline, 'deterministic', DecisionDisposition::ProviderUnavailable, providerError: 'provider_not_configured');
        }

        try {
            $result = $this->model->decide($request->publicState, $request->criteria, $request->model);

            if (
                ($result->candidateId !== null && !array_key_exists($result->candidateId, $request->criteria))
                || ($result->candidateId === null && $result->answerProbability !== null)
                || ($result->candidateId !== null && $result->answerProbability === null)
                || ($result->answerProbability !== null && (
                    !is_finite($result->answerProbability)
                    || $result->answerProbability < 0.0
                    || $result->answerProbability > 1.0
                ))
            ) {
                throw new UnexpectedValueException('Provider returned a non-canonical or invalid decision.');
            }

            // Public structural metadata cannot prove authority-bearing actions
            // safe. The caller explicitly lists options requiring full evidence.
            if ($result->candidateId !== null && in_array($result->candidateId, $request->choicesRequiringEvidence, true)) {
                return new DecisionAdvice($mode, $baselineDisposition, $baseline, 'deterministic', DecisionDisposition::Abstain, providerError: 'insufficient_decision_evidence');
            }

            $providerDisposition = $result->candidateId === null ? DecisionDisposition::Abstain : DecisionDisposition::Suggest;
            if ($mode === DecisionMode::Assist && $result->candidateId !== null) {
                return new DecisionAdvice(
                    $mode,
                    DecisionDisposition::Suggest,
                    $result->candidateId,
                    'decision_model',
                    $providerDisposition,
                    $result->candidateId,
                    $result->answerProbability,
                );
            }

            return new DecisionAdvice(
                $mode,
                $baselineDisposition,
                $baseline,
                'deterministic',
                $providerDisposition,
                $result->candidateId,
                $result->answerProbability,
            );
        } catch (UnexpectedValueException) {
            return new DecisionAdvice($mode, $baselineDisposition, $baseline, 'deterministic', DecisionDisposition::InvalidResponse, providerError: 'response_schema');
        } catch (Throwable) {
            return new DecisionAdvice($mode, $baselineDisposition, $baseline, 'deterministic', DecisionDisposition::ProviderUnavailable, providerError: 'provider_unavailable');
        }
    }
}
