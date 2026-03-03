<?php

declare(strict_types=1);

/**
 * Token configuration value object
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

use InvalidArgumentException;

/**
 * Immutable configuration for token generation and validation
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
final readonly class TokenConfig
{
    /**
     * @param string $secret Secret key for signing tokens
     * @param int $tokenLifetime Token expiry in seconds (-1 = no expiry)
     * @param int $timeout Storage cleanup timeout in seconds
     */
    public function __construct(
        public string $secret,
        public int $tokenLifetime = -1,
        public int $timeout = 86400
    ) {
        if ($secret === '') {
            throw new InvalidArgumentException('Secret cannot be empty');
        }

        if ($timeout < 0) {
            throw new InvalidArgumentException('Timeout must be non-negative');
        }
    }

    /**
     * Create default configuration
     *
     * @param string $secret Secret key for signing
     * @return self
     */
    public static function default(string $secret): self
    {
        return new self($secret);
    }

    /**
     * Create new config with different token lifetime
     *
     * @param int $seconds Lifetime in seconds (-1 = no expiry)
     * @return self
     */
    public function withLifetime(int $seconds): self
    {
        return new self($this->secret, $seconds, $this->timeout);
    }

    /**
     * Create new config with different storage timeout
     *
     * @param int $seconds Timeout in seconds
     * @return self
     */
    public function withTimeout(int $seconds): self
    {
        return new self($this->secret, $this->tokenLifetime, $seconds);
    }
}
