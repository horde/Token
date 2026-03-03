<?php

declare(strict_types=1);

/**
 * Token validation service
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

namespace Horde\Token;

use Horde\Token\Exception\ExpiredTokenException;
use Horde\Token\Exception\InvalidTokenException;
use Horde\Token\Exception\UsedTokenException;
use Horde\Token\Internal\Encoder;
use Horde\Token\Internal\Nonce;
use Horde\Token\Internal\Signer;
use Horde\Token\Storage\TokenStorageInterface;

/**
 * Token validation logic with optional storage-based replay protection
 *
 * Validates tokens by:
 * 1. Decoding from Base64 URL format
 * 2. Verifying HMAC-SHA256 signature
 * 3. Checking expiration (if configured)
 * 4. Checking for replay attacks (if unique validation requested)
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
final class TokenValidator
{
    /**
     * @param TokenConfig $config Token configuration
     * @param TokenStorageInterface $storage Storage backend for replay protection
     */
    public function __construct(
        private readonly TokenConfig $config,
        private readonly TokenStorageInterface $storage
    ) {
    }

    /**
     * Check if token is valid (non-throwing version)
     *
     * Validates the token signature and expiration but does NOT
     * check for replay attacks or mark the token as used.
     *
     * @param string $token The token to validate
     * @param string $seed The seed used during generation
     * @param int|null $timeout Custom timeout in seconds (null = use config)
     * @return bool True if token is valid
     *
     * @example
     * if ($validator->isValid($token, 'form_id')) {
     *     // Token is valid
     * }
     */
    public function isValid(
        string $token,
        string $seed = '',
        ?int $timeout = null
    ): bool {
        try {
            $this->validateInternal($token, $seed, $timeout, unique: false);
            return true;
        } catch (InvalidTokenException | ExpiredTokenException) {
            return false;
        }
    }

    /**
     * Validate token and mark as used (one-time use)
     *
     * This method:
     * 1. Validates signature and expiration
     * 2. Checks if token was previously used
     * 3. Marks token as used to prevent replay attacks
     *
     * @param string $token The token to validate
     * @param string $seed The seed used during generation
     * @return void
     * @throws InvalidTokenException If signature is invalid
     * @throws ExpiredTokenException If token has expired
     * @throws UsedTokenException If token was already used
     *
     * @example
     * try {
     *     $validator->validateUnique($token, 'delete_user');
     *     // Token valid, proceed with action
     * } catch (UsedTokenException $e) {
     *     // Token already used (replay attack)
     * }
     */
    public function validateUnique(string $token, string $seed = ''): void
    {
        $this->validateInternal($token, $seed, timeout: null, unique: true);
    }

    /**
     * Internal validation logic
     *
     * @param string $token The token to validate
     * @param string $seed The seed used during generation
     * @param int|null $timeout Custom timeout (null = use config)
     * @param bool $unique Whether to check for replay and mark as used
     * @return void
     * @throws InvalidTokenException If signature is invalid or token is malformed
     * @throws ExpiredTokenException If token has expired
     * @throws UsedTokenException If token was already used (unique validation only)
     */
    private function validateInternal(
        string $token,
        string $seed,
        ?int $timeout,
        bool $unique
    ): void {
        // Decode token from Base64 URL format
        try {
            $decoded = Encoder::decode($token);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidTokenException('Invalid token encoding: ' . $e->getMessage(), 0, $e);
        }

        // Token must be at least 38 bytes (6 byte nonce + 32 byte signature)
        if (strlen($decoded) < 38) {
            throw new InvalidTokenException('Token too short (minimum 38 bytes required)');
        }

        // Extract nonce and signature
        $nonceBytes = substr($decoded, 0, 6);
        $signature = substr($decoded, 6);

        // Verify signature
        $expectedSignature = Signer::sign($nonceBytes . $seed, $this->config->secret);
        if (!hash_equals($expectedSignature, $signature)) {
            throw new InvalidTokenException('Invalid token signature');
        }

        // Check expiration
        $this->checkExpiration($nonceBytes, $timeout);

        // Check uniqueness (replay protection)
        if ($unique) {
            $this->checkUniqueness($token);
        }
    }

    /**
     * Check if token has expired
     *
     * @param string $nonceBytes Raw nonce bytes (6 bytes)
     * @param int|null $timeout Custom timeout (null = use config)
     * @return void
     * @throws ExpiredTokenException If token has expired
     */
    private function checkExpiration(string $nonceBytes, ?int $timeout): void
    {
        // Use provided timeout or fall back to config
        $timeout ??= $this->config->tokenLifetime;

        // Negative timeout means no expiration
        if ($timeout < 0) {
            return;
        }

        // Extract timestamp from nonce
        $nonce = Nonce::fromBytes($nonceBytes);
        $timestamp = $nonce->timestamp();

        // Check if expired
        $age = time() - $timestamp;
        if ($age >= $timeout) {
            throw new ExpiredTokenException(
                sprintf(
                    'Token expired %d seconds ago (lifetime: %d seconds)',
                    $age - $timeout,
                    $timeout
                )
            );
        }
    }

    /**
     * Check if token has been used before and mark as used
     *
     * @param string $token The token to check
     * @return void
     * @throws UsedTokenException If token was already used
     */
    private function checkUniqueness(string $token): void
    {
        // Clean up expired tokens first
        $this->storage->purge();

        // Check if token exists
        if ($this->storage->exists($token)) {
            throw new UsedTokenException('Token has been used before (replay attack detected)');
        }

        // Mark token as used
        $this->storage->add($token);
    }
}
