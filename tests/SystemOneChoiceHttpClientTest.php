<?php

declare(strict_types=1);

namespace voku\AgentDecision\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use voku\AgentDecision\Provider\SystemOneChoiceHttpClient;

final class SystemOneChoiceHttpClientTest extends TestCase
{
    public function testRejectsUntrustedEndpointsAndInvalidTimeouts(): void
    {
        foreach ([
            'http://example.org/v1/systemone',
            'https://127.0.0.1/v1/systemone',
            'https://localhost/v1/systemone',
            'https://10.0.0.1/v1/systemone',
            'https://example.org/v1/other',
            'https://user:password@example.org/v1/systemone',
            'https://example.org/v1/systemone?token=abc',
        ] as $url) {
            try {
                new SystemOneChoiceHttpClient($url, 1000);
                self::fail('Untrusted URL accepted: ' . $url);
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }

        foreach ([99, 10001] as $timeout) {
            try {
                new SystemOneChoiceHttpClient('https://example.org/v1/systemone', $timeout);
                self::fail('Invalid timeout accepted: ' . $timeout);
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testExplicitNumericLoopbackIsOnlyForLocalFixtures(): void
    {
        $client = new SystemOneChoiceHttpClient('http://127.0.0.1:8282/v1/systemone', 1000, true);
        self::assertInstanceOf(SystemOneChoiceHttpClient::class, $client);
    }
}
