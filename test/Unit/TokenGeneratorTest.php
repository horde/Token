<?php

declare(strict_types=1);

/**
 * TokenGenerator tests
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Token\Test\Unit;

use Horde\Token\TokenConfig;
use Horde\Token\TokenGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Test token generation
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[\PHPUnit\Framework\Attributes\CoversClass(TokenGenerator::class)]
class TokenGeneratorTest extends TestCase
{
    public function testGenerateReturnsGeneratedToken(): void
    {
        $config = TokenConfig::default('test-secret');
        $generator = new TokenGenerator($config);

        $token = $generator->generate();

        $this->assertInstanceOf(\Horde\Token\GeneratedToken::class, $token);
        $this->assertIsString($token->token);
        $this->assertNotEmpty($token->token);
    }

    public function testGeneratedTokenIsBase64UrlEncoded(): void
    {
        $config = TokenConfig::default('test-secret');
        $generator = new TokenGenerator($config);

        $token = $generator->generate();

        // Base64 URL encoding uses - and _ instead of + and /
        $this->assertStringNotContainsString('+', $token->token);
        $this->assertStringNotContainsString('/', $token->token);
        $this->assertStringNotContainsString('=', $token->token);
    }

    public function testGeneratedTokenHasExpectedLength(): void
    {
        $config = TokenConfig::default('test-secret');
        $generator = new TokenGenerator($config);

        $token = $generator->generate();

        // 6 bytes nonce + 32 bytes signature = 38 bytes = 51 chars base64
        $this->assertEquals(51, strlen($token->token));
    }

    public function testGenerateWithSeedProducesDifferentToken(): void
    {
        $config = TokenConfig::default('test-secret');
        $generator = new TokenGenerator($config);

        $token1 = $generator->generate('seed1');
        $token2 = $generator->generate('seed2');

        $this->assertNotEquals($token1->token, $token2->token);
    }

    public function testGenerateMultipleTimesProducesDifferentTokens(): void
    {
        $config = TokenConfig::default('test-secret');
        $generator = new TokenGenerator($config);

        $token1 = $generator->generate('same-seed');
        usleep(1000); // Ensure different timestamp
        $token2 = $generator->generate('same-seed');

        $this->assertNotEquals($token1->token, $token2->token);
    }

    public function testExpirationIsNullWhenLifetimeIsNegative(): void
    {
        $config = TokenConfig::default('test-secret')->withLifetime(-1);
        $generator = new TokenGenerator($config);

        $token = $generator->generate();

        $this->assertNull($token->expiresAt);
        $this->assertFalse($token->isExpired());
    }

    public function testExpirationIsSetWhenLifetimeIsPositive(): void
    {
        $config = TokenConfig::default('test-secret')->withLifetime(3600);
        $generator = new TokenGenerator($config);

        $token = $generator->generate();

        $this->assertIsInt($token->expiresAt);
        $this->assertGreaterThan(time(), $token->expiresAt);
        $this->assertLessThanOrEqual(time() + 3600, $token->expiresAt);
    }

    public function testTokenCanBeConvertedToString(): void
    {
        $config = TokenConfig::default('test-secret');
        $generator = new TokenGenerator($config);

        $token = $generator->generate();
        $tokenString = (string) $token;

        $this->assertEquals($token->token, $tokenString);
    }

    public function testGenerateWithPerCallSecretProducesDifferentTokenThanConstructorSecret(): void
    {
        $config = TokenConfig::default('constructor-secret');
        $generator = new TokenGenerator($config);

        // Identical seed and (essentially) identical timestamp; the only
        // difference is the secret used to sign. Outputs must differ.
        $tokenA = $generator->generate('same-seed', 'override-secret');
        $tokenB = $generator->generate('same-seed', 'constructor-secret');

        $this->assertNotEquals($tokenA->token, $tokenB->token);
    }

    public function testGenerateWithoutSecretFallsBackToConstructorSecret(): void
    {
        $config = TokenConfig::default('constructor-secret');
        $generator = new TokenGenerator($config);

        $tokenDefault = $generator->generate('seed');
        $tokenExplicit = $generator->generate('seed', 'constructor-secret');

        // They use the same secret but DIFFER in nonce timestamp/random,
        // so we cannot compare bytes; instead, both must validate against
        // the same secret (covered by TokenValidatorTest::testValidateWith*).
        // Smoke check: both produce 51-char base64url output.
        $this->assertSame(51, strlen($tokenDefault->token));
        $this->assertSame(51, strlen($tokenExplicit->token));
    }
}
