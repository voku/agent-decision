<?php

declare(strict_types=1);

namespace voku\AgentDecision;

/**
 * External signals are opt-in and never mutate a caller's workflow.
 */
enum DecisionMode: string
{
    case Off = 'off';
    case Rules = 'rules';
    case Shadow = 'shadow';
    case Assist = 'assist';
}
