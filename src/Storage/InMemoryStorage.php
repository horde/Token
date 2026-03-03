<?php

declare(strict_types=1);

/**
 * In-memory token storage (for testing)
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

namespace Horde\Token\Storage;

/**
 * In-memory storage implementation
 *
 * Stores tokens in memory. Data is lost when the process ends.
 * Only suitable for testing and single-request scenarios.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
final class InMemoryStorage implements TokenStorageInterface
{
    /**
     * @var array<string, int> Token storage (tokenId => timestamp)
     */
    private array $tokens = [];

    /**
     * @param int $timeout Token timeout for purging (seconds)
     */
    public function __construct(
        private readonly int $timeout = 86400
    ) {
    }

    /**
     * Check if token exists in storage
     *
     * @param string $tokenId The token identifier
     * @return bool True if token exists
     */
    public function exists(string $tokenId): bool
    {
        return isset($this->tokens[$tokenId]);
    }

    /**
     * Add token to storage
     *
     * @param string $tokenId The token identifier
     * @return void
     */
    public function add(string $tokenId): void
    {
        $this->tokens[$tokenId] = time();
    }

    /**
     * Remove expired tokens from storage
     *
     * @return void
     */
    public function purge(): void
    {
        $cutoff = time() - $this->timeout;

        $this->tokens = array_filter(
            $this->tokens,
            fn(int $timestamp): bool => $timestamp >= $cutoff
        );
    }

    /**
     * Get all stored tokens (for testing)
     *
     * @return array<string, int> Token ID => timestamp
     */
    public function getAll(): array
    {
        return $this->tokens;
    }

    /**
     * Clear all tokens (for testing)
     *
     * @return void
     */
    public function clear(): void
    {
        $this->tokens = [];
    }

    /**
     * Get count of stored tokens (for testing)
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->tokens);
    }
}
