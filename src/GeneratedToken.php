<?php

declare(strict_types=1);

/**
 * Generated token value object
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

/**
 * Represents a generated CSRF token with metadata
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
final readonly class GeneratedToken
{
    /**
     * @param string $token The token string
     * @param int|null $expiresAt Unix timestamp when token expires (null = never)
     */
    public function __construct(
        public string $token,
        public ?int $expiresAt = null
    ) {}

    /**
     * Check if token is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expiresAt !== null && time() >= $this->expiresAt;
    }

    /**
     * Get seconds until expiration
     *
     * @return int|null Seconds remaining (negative if expired, null if no expiry)
     */
    public function getSecondsUntilExpiration(): ?int
    {
        return $this->expiresAt !== null
            ? $this->expiresAt - time()
            : null;
    }

    /**
     * Convert to string (returns token)
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->token;
    }
}
