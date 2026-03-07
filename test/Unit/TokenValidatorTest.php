<?php

declare(strict_types=1);

/**
 * TokenValidator tests
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

use Horde\Token\Exception\ExpiredTokenException;
use Horde\Token\Exception\InvalidTokenException;
use Horde\Token\Exception\UsedTokenException;
use Horde\Token\Storage\InMemoryStorage;
use Horde\Token\TokenConfig;
use Horde\Token\TokenGenerator;
use Horde\Token\TokenValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test token validation
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[\PHPUnit\Framework\Attributes\CoversClass(TokenValidator::class)]
class TokenValidatorTest extends TestCase
{
    public function testIsValidReturnsTrueForValidToken(): void
    {
        $config = TokenConfig::default('test-secret');
        $storage = new InMemoryStorage();
        $generator = new TokenGenerator($config);
        $validator = new TokenValidator($config, $storage);

        $token = $generator->generate('test-seed');

        $this->assertTrue($validator->isValid($token->token, 'test-seed'));
    }

    public function testIsValidReturnsFalseForInvalidSignature(): void
    {
        $config = TokenConfig::default('test-secret');
        $storage = new InMemoryStorage();
        $validator = new TokenValidator($config, $storage);

        $this->assertFalse($validator->isValid('invalid_token', 'test-seed'));
    }

    public function testIsValidReturnsFalseForWrongSeed(): void
    {
        $config = TokenConfig::default('test-secret');
        $storage = new InMemoryStorage();
        $generator = new TokenGenerator($config);
        $validator = new TokenValidator($config, $storage);

        $token = $generator->generate('correct-seed');

        $this->assertFalse($validator->isValid($token->token, 'wrong-seed'));
    }

    public function testValidateUniqueSucceedsForFirstUse(): void
    {
        $config = TokenConfig::default('test-secret');
        $storage = new InMemoryStorage();
        $generator = new TokenGenerator($config);
        $validator = new TokenValidator($config, $storage);

        $token = $generator->generate('test-seed');

        $validator->validateUnique($token->token, 'test-seed');
        $this->assertTrue(true); // No exception thrown
    }

    public function testValidateUniqueThrowsForSecondUse(): void
    {
        $config = TokenConfig::default('test-secret');
        $storage = new InMemoryStorage();
        $generator = new TokenGenerator($config);
        $validator = new TokenValidator($config, $storage);

        $token = $generator->generate('test-seed');

        $validator->validateUnique($token->token, 'test-seed');

        $this->expectException(UsedTokenException::class);
        $validator->validateUnique($token->token, 'test-seed');
    }

    public function testValidateUniqueThrowsForInvalidSignature(): void
    {
        $config = TokenConfig::default('test-secret');
        $storage = new InMemoryStorage();
        $validator = new TokenValidator($config, $storage);

        $this->expectException(InvalidTokenException::class);
        $validator->validateUnique('invalid_token', 'test-seed');
    }

    public function testExpiredTokenIsDetected(): void
    {
        $config = TokenConfig::default('test-secret')->withLifetime(1);
        $storage = new InMemoryStorage();
        $generator = new TokenGenerator($config);
        $validator = new TokenValidator($config, $storage);

        $token = $generator->generate('test-seed');
        sleep(2); // Wait for expiration

        $this->assertFalse($validator->isValid($token->token, 'test-seed'));
    }

    public function testExpiredTokenThrowsExpiredException(): void
    {
        $config = TokenConfig::default('test-secret')->withLifetime(1);
        $storage = new InMemoryStorage();
        $generator = new TokenGenerator($config);
        $validator = new TokenValidator($config, $storage);

        $token = $generator->generate('test-seed');
        sleep(2); // Wait for expiration

        $this->expectException(ExpiredTokenException::class);
        $validator->validateUnique($token->token, 'test-seed');
    }

    public function testCustomTimeoutOverridesConfig(): void
    {
        $config = TokenConfig::default('test-secret')->withLifetime(3600);
        $storage = new InMemoryStorage();
        $generator = new TokenGenerator($config);
        $validator = new TokenValidator($config, $storage);

        $token = $generator->generate('test-seed');

        // Even though config says 3600s, we check with 0s timeout
        $this->assertFalse($validator->isValid($token->token, 'test-seed', timeout: 0));
    }

    public function testNegativeTimeoutDisablesExpiration(): void
    {
        $config = TokenConfig::default('test-secret')->withLifetime(1);
        $storage = new InMemoryStorage();
        $generator = new TokenGenerator($config);
        $validator = new TokenValidator($config, $storage);

        $token = $generator->generate('test-seed');
        sleep(2); // Wait past configured expiration

        // With negative timeout, token should still be valid
        $this->assertTrue($validator->isValid($token->token, 'test-seed', timeout: -1));
    }

    public function testStoragePurgeIsCalledDuringUniqueValidation(): void
    {
        $config = TokenConfig::default('test-secret');
        $storage = new InMemoryStorage(timeout: 1);
        $generator = new TokenGenerator($config);
        $validator = new TokenValidator($config, $storage);

        // Add some tokens
        $storage->add('old_token_1');
        $storage->add('old_token_2');

        $this->assertEquals(2, $storage->count());

        sleep(2); // Wait for tokens to expire

        // Validate unique should trigger purge
        $token = $generator->generate('test');
        $validator->validateUnique($token->token, 'test');

        // Old tokens should be purged, only new token remains
        $this->assertEquals(1, $storage->count());
    }
}
