<?php

declare(strict_types=1);

namespace voku\AgentDecision;

/**
 * An explicit caller assertion, not an automatic privacy classifier.
 */
enum DataClassification: string
{
    case Unclassified = 'unclassified';
    case Public = 'public';
}
