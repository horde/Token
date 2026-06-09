<?php

declare(strict_types=1);

/**
 * Token generation service
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

use Horde\Token\Internal\Encoder;
use Horde\Token\Internal\Nonce;
use Horde\Token\Internal\Signer;

/**
 * Pure token generation logic without storage dependencies
 *
 * Generates cryptographically signed CSRF tokens using:
 * - Nonce (timestamp + random data)
 * - HMAC-SHA256 signature
 * - Base64 URL-safe encoding
 *
 * Token format: Base64URL(nonce[6 bytes] + HMAC[32 bytes]) = 51 characters
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
final class TokenGenerator
{
    /**
     * @param TokenConfig $config Token configuration
     */
    public function __construct(
        private readonly TokenConfig $config
    ) {}

    /**
     * Generate a new CSRF token
     *
     * The token is signed with HMAC-SHA256 using the configured secret
     * (or the per-call override, if supplied). An optional seed can be
     * included to bind the token to a specific context (e.g., form name,
     * user action).
     *
     * Pass a per-call $secret to sign with a different key without
     * constructing a new TokenGenerator. This is the preferred way for
     * services that process multiple secrets in a single request (for
     * example, iterating sessions in an admin tool, or composing tokens
     * for multiple identities). Constructing many TokenGenerator
     * instances is feasible but wasteful — the rest of the configuration
     * is invariant across calls.
     *
     * @param string      $seed   Optional seed for context binding
     * @param string|null $secret Per-call secret override; null uses the
     *                            configured secret (default behaviour).
     * @return GeneratedToken The generated token with expiry information
     *
     * @example
     * $generator = new TokenGenerator($config);
     * $token = $generator->generate('checkout_form');
     * echo $token->token;  // "base64url_encoded_token_string"
     *
     * @example
     * // Per-call secret for session-bound token without rebuilding the generator
     * $token = $generator->generate('checkout_form', $session->getSecret());
     */
    public function generate(string $seed = '', ?string $secret = null): GeneratedToken
    {
        // Generate nonce (timestamp + random data)
        $nonce = Nonce::generate();

        // Create signature: HMAC-SHA256(nonce + seed, secret)
        $signature = Signer::sign(
            $nonce->bytes() . $seed,
            $secret ?? $this->config->secret
        );

        // Encode: Base64URL(nonce + signature)
        $token = Encoder::encode($nonce->bytes() . $signature);

        // Calculate expiration timestamp
        $expiresAt = $this->calculateExpiry($nonce);

        return new GeneratedToken($token, $expiresAt);
    }

    /**
     * Calculate token expiration timestamp
     *
     * @param Nonce $nonce The nonce containing the timestamp
     * @return int|null Unix timestamp when token expires (null if no expiry)
     */
    private function calculateExpiry(Nonce $nonce): ?int
    {
        if ($this->config->tokenLifetime < 0) {
            // No expiry
            return null;
        }

        return $nonce->timestamp() + $this->config->tokenLifetime;
    }
}
