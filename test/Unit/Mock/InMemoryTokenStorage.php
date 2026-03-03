<?php

declare(strict_types=1);

/**
 * In-memory mock storage implementation for testing.
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

namespace Horde\Token\Test\Unit\Mock;

/**
 * In-memory mock storage for testing token business logic.
 *
 * This implementation stores tokens in memory, allowing tests
 * to verify token business logic without requiring a database.
 *
 * NOTE: This pattern should be migrated to Horde\Db once that
 * package is modernized, providing a reusable mock DB interface
 * for all Horde components.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class InMemoryTokenStorage implements TokenStorageInterface
{
    /**
     * @var array<string, int> Token storage (tokenId => timestamp)
     */
    private array $tokens = [];

    public function exists(string $tokenId): bool
    {
        return isset($this->tokens[$tokenId]);
    }

    public function add(string $tokenId): void
    {
        $this->tokens[$tokenId] = time();
    }

    public function purge(int $timeout): void
    {
        $cutoff = time() - $timeout;
        $this->tokens = array_filter(
            $this->tokens,
            fn ($timestamp) => $timestamp > $cutoff
        );
    }

    /**
     * Get all stored tokens (for testing purposes).
     *
     * @return array<string, int>
     */
    public function getAll(): array
    {
        return $this->tokens;
    }

    /**
     * Clear all tokens (for test cleanup).
     *
     * @return void
     */
    public function clear(): void
    {
        $this->tokens = [];
    }
}
