<?php

declare(strict_types=1);

namespace Horde\Token\Test\Unit;

use Horde\Token\Exception\ExpiredTokenException;
use Horde\Token\Exception\InvalidTokenException;
use Horde\Token\Exception\UsedTokenException;
use Horde\Token\GeneratedToken;
use Horde\Token\Storage\NullStorage;
use Horde\Token\Token;
use Horde\Token\TokenConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Token facade class
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[CoversClass(Token::class)]
class TokenTest extends TestCase
{
    private string $secret = 'test-secret-key-for-testing';

    public function testFileFactoryCreatesTokenService(): void
    {
        $tokenDir = sys_get_temp_dir() . '/token-test-' . uniqid();
        mkdir($tokenDir);

        try {
            $token = Token::file($this->secret, $tokenDir);

            $this->assertInstanceOf(Token::class, $token);
            $this->assertInstanceOf(TokenConfig::class, $token->getConfig());
        } finally {
            // Cleanup
            if (is_dir($tokenDir)) {
                array_map('unlink', glob($tokenDir . '/*'));
                rmdir($tokenDir);
            }
        }
    }

    public function testFileFactoryWithCustomConfig(): void
    {
        $tokenDir = sys_get_temp_dir() . '/token-test-' . uniqid();
        mkdir($tokenDir);

        try {
            $config = new TokenConfig($this->secret, 3600);
            $token = Token::file($this->secret, $tokenDir, $config);

            $this->assertSame($config, $token->getConfig());
        } finally {
            // Cleanup
            if (is_dir($tokenDir)) {
                array_map('unlink', glob($tokenDir . '/*'));
                rmdir($tokenDir);
            }
        }
    }

    public function testNullFactoryCreatesTokenService(): void
    {
        $token = Token::null($this->secret);

        $this->assertInstanceOf(Token::class, $token);
        $this->assertInstanceOf(NullStorage::class, $token->getStorage());
    }

    public function testNullFactoryWithCustomConfig(): void
    {
        $config = new TokenConfig($this->secret, 7200);
        $token = Token::null($this->secret, $config);

        $this->assertSame($config, $token->getConfig());
    }

    public function testGenerateReturnsGeneratedToken(): void
    {
        $token = Token::null($this->secret);
        $generated = $token->generate('test-seed');

        $this->assertInstanceOf(GeneratedToken::class, $generated);
        $this->assertNotEmpty($generated->token);
    }

    public function testIsValidReturnsTrueForValidToken(): void
    {
        $token = Token::null($this->secret);
        $generated = $token->generate('test-seed');

        $this->assertTrue($token->isValid($generated->token, 'test-seed'));
    }

    public function testIsValidReturnsFalseForInvalidToken(): void
    {
        $token = Token::null($this->secret);

        $this->assertFalse($token->isValid('invalid-token', 'test-seed'));
    }

    public function testIsValidReturnsFalseForWrongSeed(): void
    {
        $token = Token::null($this->secret);
        $generated = $token->generate('correct-seed');

        $this->assertFalse($token->isValid($generated->token, 'wrong-seed'));
    }

    public function testIsValidWithCustomTimeout(): void
    {
        $token = Token::null($this->secret);
        $generated = $token->generate('test-seed');

        // Should still be valid with long timeout
        $this->assertTrue($token->isValid($generated->token, 'test-seed', 3600));
    }

    public function testValidateUniqueSucceedsForValidToken(): void
    {
        $token = Token::null($this->secret);
        $generated = $token->generate('test-seed');

        // Should not throw
        $token->validateUnique($generated->token, 'test-seed');
        $this->assertTrue(true); // Assert we got here
    }

    public function testValidateUniqueThrowsForInvalidToken(): void
    {
        $token = Token::null($this->secret);

        $this->expectException(InvalidTokenException::class);
        $token->validateUnique('invalid-token', 'test-seed');
    }

    public function testValidateUniqueThrowsForWrongSeed(): void
    {
        $token = Token::null($this->secret);
        $generated = $token->generate('correct-seed');

        $this->expectException(InvalidTokenException::class);
        $token->validateUnique($generated->token, 'wrong-seed');
    }

    public function testValidateUniqueThrowsForExpiredToken(): void
    {
        // Create token with very short timeout
        $config = new TokenConfig($this->secret, 0);
        $token = Token::null($this->secret, $config);
        $generated = $token->generate('test-seed');

        // Wait for token to expire
        sleep(1);

        $this->expectException(ExpiredTokenException::class);
        $token->validateUnique($generated->token, 'test-seed');
    }

    public function testPurgeCallsStoragePurge(): void
    {
        $tokenDir = sys_get_temp_dir() . '/token-test-' . uniqid();
        mkdir($tokenDir);

        try {
            $token = Token::file($this->secret, $tokenDir);

            // Create some tokens
            $token->generate('test1');
            $token->generate('test2');

            // Purge should succeed without errors
            $token->purge();

            $this->assertTrue(true); // Assert we got here
        } finally {
            // Cleanup
            if (is_dir($tokenDir)) {
                array_map('unlink', glob($tokenDir . '/*'));
                rmdir($tokenDir);
            }
        }
    }

    public function testGetConfigReturnsConfiguration(): void
    {
        $config = new TokenConfig($this->secret, 1800);
        $token = Token::null($this->secret, $config);

        $this->assertSame($config, $token->getConfig());
    }

    public function testGetStorageReturnsStorageBackend(): void
    {
        $token = Token::null($this->secret);
        $storage = $token->getStorage();

        $this->assertInstanceOf(NullStorage::class, $storage);
    }

    public function testGenerateWithoutSeedWorks(): void
    {
        $token = Token::null($this->secret);
        $generated = $token->generate();

        $this->assertInstanceOf(GeneratedToken::class, $generated);
        $this->assertNotEmpty($generated->token);
    }

    public function testIsValidWithoutSeedWorks(): void
    {
        $token = Token::null($this->secret);
        $generated = $token->generate();

        $this->assertTrue($token->isValid($generated->token));
    }

    public function testValidateUniqueWithoutSeedWorks(): void
    {
        $token = Token::null($this->secret);
        $generated = $token->generate();

        // Should not throw
        $token->validateUnique($generated->token);
        $this->assertTrue(true); // Assert we got here
    }

    public function testGenerateAndIsValidWithPerCallSecretRoundTrip(): void
    {
        $token = Token::null('constructor-secret');

        // Sign with override; verify with same override; must validate.
        $generated = $token->generate('seed', 'override-secret');
        $this->assertTrue(
            $token->isValid($generated->token, 'seed', null, 'override-secret')
        );

        // Verify with constructor secret; must reject.
        $this->assertFalse(
            $token->isValid($generated->token, 'seed')
        );
    }

    public function testValidateUniqueWithPerCallSecretRoundTrip(): void
    {
        $token = Token::null('constructor-secret');

        $generated = $token->generate('seed', 'override-secret');

        // Same override must validate without throwing
        $token->validateUnique($generated->token, 'seed', 'override-secret');
        $this->assertTrue(true);
    }
}
