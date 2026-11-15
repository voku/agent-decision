<?php

declare(strict_types=1);

namespace voku\AgentDecision\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use voku\AgentDecision\BoundedDecisionAdvisor;
use voku\AgentDecision\DataClassification;
use voku\AgentDecision\DecisionAdvice;
use voku\AgentDecision\DecisionChoiceResult;
use voku\AgentDecision\DecisionMode;
use voku\AgentDecision\DecisionModel;
use voku\AgentDecision\DecisionRequest;

/**
 * A fake provider does not prove decision quality, only contract and safety.
 */
final class BoundedDecisionAdvisorTest extends TestCase
{
    public function testOfflineAndRulesNeverInvokeModel(): void
    {
        $model = new FixedDecisionModel('reviewer', 0.8);
        foreach ([DecisionMode::Off, DecisionMode::Rules] as $mode) {
            $advice = (new BoundedDecisionAdvisor($model))->advise(self::request(), $mode);
            self::assertSame('deterministic', $advice->source);
            self::assertSame('investigator', $advice->suggestedChoice);
            self::assertSame('not_enabled', $advice->providerDisposition->value);
        }

        self::assertSame(0, $model->calls);
    }

    public function testShadowObservesWithoutOverridingBaseline(): void
    {
        $model = new FixedDecisionModel('reviewer', 0.83);
        $advice = (new BoundedDecisionAdvisor($model))->advise(self::request(), DecisionMode::Shadow);

        self::assertSame('deterministic', $advice->source);
        self::assertSame('investigator', $advice->suggestedChoice);
        self::assertSame('reviewer', $advice->providerObservedChoice);
        self::assertSame(0.83, $advice->providerProbability);
        self::assertSame(1, $model->calls);
        self::assertFalse($advice->toArray()['lifecycle_mutation']);
        self::assertArrayNotHasKey('human_decision_required', $advice->toArray());
    }

    public function testAssistAcceptsOnlyCanonicalUnprotectedSuggestion(): void
    {
        $model = new FixedDecisionModel('reviewer', 0.85);
        $advice = (new BoundedDecisionAdvisor($model))->advise(self::request(), DecisionMode::Assist);

        self::assertSame('decision_model', $advice->source);
        self::assertSame('reviewer', $advice->suggestedChoice);
        self::assertSame('suggest', $advice->disposition->value);
        self::assertSame('advisory_only', $advice->toArray()['authority']);
        self::assertFalse($advice->toArray()['lifecycle_mutation']);
    }

    public function testUnclassifiedStateNeverLeavesProcess(): void
    {
        $model = new FixedDecisionModel('reviewer', 0.9);
        $request = new DecisionRequest(
            criteria: self::options(),
            publicState: 'Private data must not be transported',
            classification: DataClassification::Unclassified,
            baselineChoice: 'investigator',
        );

        $advice = (new BoundedDecisionAdvisor($model))->advise($request, DecisionMode::Assist);
        self::assertSame('not_enabled', $advice->providerDisposition->value);
        self::assertSame('public_classification_required', $advice->providerError);
        self::assertSame(0, $model->calls);
    }

    public function testProtectedPositiveActionsCannotBeSuggestedFromMetadata(): void
    {
        $model = new FixedDecisionModel('approve', 0.99);
        $request = new DecisionRequest(
            criteria: ['approve' => 'Explicit human sign-off', 'request_changes' => 'Revisit the evidence'],
            publicState: '{"scope_count":1}',
            classification: DataClassification::Public,
            baselineChoice: 'request_changes',
            choicesRequiringEvidence: ['approve'],
        );

        foreach ([DecisionMode::Assist, DecisionMode::Shadow] as $mode) {
            $advice = (new BoundedDecisionAdvisor($model))->advise($request, $mode);
            self::assertSame('request_changes', $advice->suggestedChoice);
            self::assertSame('abstain', $advice->providerDisposition->value);
            self::assertSame('insufficient_decision_evidence', $advice->providerError);
            self::assertNull($advice->providerObservedChoice);
        }
    }

    public function testAbstentionNeverOverridesKnownBaseline(): void
    {
        $model = new FixedDecisionModel(null, null);
        $advice = (new BoundedDecisionAdvisor($model))->advise(self::request(), DecisionMode::Assist);
        self::assertSame('investigator', $advice->suggestedChoice);
        self::assertSame('abstain', $advice->providerDisposition->value);
    }

    public function testProviderFailuresAndInvalidChoicesKeepTheBaseline(): void
    {
        foreach ([
            ['fabricated', 0.95],
            ['reviewer', NAN],
            ['reviewer', INF],
            ['reviewer', -0.1],
            ['reviewer', 1.1],
            ['reviewer', null],
            [null, 0.8],
        ] as [$candidate, $probability]) {
            $model = new FixedDecisionModel($candidate, $probability);
            $advice = (new BoundedDecisionAdvisor($model))->advise(self::request(), DecisionMode::Assist);
            self::assertSame('invalid_response', $advice->providerDisposition->value);
            self::assertSame('response_schema', $advice->providerError);
            self::assertSame('investigator', $advice->suggestedChoice);
            self::assertSame('deterministic', $advice->source);
        }

        $unavailable = new FixedDecisionModel('reviewer', 0.8, true);
        $advice = (new BoundedDecisionAdvisor($unavailable))->advise(self::request(), DecisionMode::Assist);
        self::assertSame('provider_unavailable', $advice->providerDisposition->value);
        self::assertSame('investigator', $advice->suggestedChoice);
    }

    public function testRequestRejectsInvalidCanonicalAndEvidenceSets(): void
    {
        $cases = [
            ['none' => 'Reserved'],
            ['' => 'Empty ID'],
            ['invalid space' => 'Bad identifier'],
            ['reviewer' => ''],
        ];

        foreach ($cases as $criteria) {
            try {
                new DecisionRequest(criteria: $criteria);
                self::fail('Invalid candidate set was accepted.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }

        try {
            new DecisionRequest(
                criteria: self::options(),
                choicesRequiringEvidence: ['unknown'],
            );
            self::fail('Unrecognized evidence choice was accepted.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }
    }

    /** @return non-empty-array<non-empty-string, non-empty-string> */
    private static function options(): array
    {
        return ['investigator' => 'Investigate the evidence', 'reviewer' => 'Independently review the evidence'];
    }

    private static function request(): DecisionRequest
    {
        return new DecisionRequest(
            criteria: self::options(),
            publicState: '{"task_type":"public_issue"}',
            classification: DataClassification::Public,
            baselineChoice: 'investigator',
        );
    }
}

final class FixedDecisionModel implements DecisionModel
{
    public int $calls = 0;

    public function __construct(
        private ?string $choice,
        private ?float $probability,
        private bool $fail = false,
    ) {
    }

    /** @param non-empty-array<string, non-empty-string> $criteria */
    public function decide(string $publicState, array $criteria, ?string $model): DecisionChoiceResult
    {
        ++$this->calls;
        if ($this->fail) {
            throw new \RuntimeException('Fixture provider unavailable');
        }

        return new DecisionChoiceResult($this->choice, $this->probability);
    }
}
