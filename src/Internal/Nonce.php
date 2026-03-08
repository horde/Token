<?php

declare(strict_types=1);

/**
 * Internal nonce generation
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @internal
 */

namespace Horde\Token\Internal;

use InvalidArgumentException;

/**
 * Generates cryptographically secure nonces
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @internal
 */
final readonly class Nonce
{
    /**
     * @param string $bytes The raw 6-byte nonce (4 bytes timestamp + 2 bytes random)
     */
    private function __construct(
        private string $bytes
    ) {}

    /**
     * Generate a new nonce
     *
     * @return self
     */
    public static function generate(): self
    {
        // Pack: 4 bytes for timestamp (N = unsigned long big-endian)
        //       2 bytes for random data (n = unsigned short big-endian)
        $nonce = pack('Nn', time(), mt_rand(0, 65535));
        return new self($nonce);
    }

    /**
     * Create from existing bytes
     *
     * @param string $bytes Raw 6-byte nonce
     * @return self
     */
    public static function fromBytes(string $bytes): self
    {
        if (strlen($bytes) !== 6) {
            throw new InvalidArgumentException('Nonce must be exactly 6 bytes');
        }
        return new self($bytes);
    }

    /**
     * Get raw bytes
     *
     * @return string
     */
    public function bytes(): string
    {
        return $this->bytes;
    }

    /**
     * Extract timestamp from nonce
     *
     * @return int Unix timestamp
     */
    public function timestamp(): int
    {
        return unpack('N', substr($this->bytes, 0, 4))[1];
    }
}
