<?php

declare(strict_types=1);

/**
 * Mock storage interface for testing token business logic without DB coupling.
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
 * Mock storage interface for testing token business logic.
 *
 * This interface decouples token business logic from database
 * implementation details, enabling clean unit tests.
 *
 * NOTE: This pattern should be migrated to Horde\Db once that
 * package is modernized, as the tight coupling of DB access
 * to business logic is an architectural issue inherited from
 * Horde 3/4 era.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
interface TokenStorageInterface
{
    /**
     * Check if a token exists in storage.
     *
     * @param string $tokenId Token identifier
     * @return bool True if token exists
     */
    public function exists(string $tokenId): bool;

    /**
     * Add a token to storage.
     *
     * @param string $tokenId Token identifier
     * @return void
     */
    public function add(string $tokenId): void;

    /**
     * Remove expired tokens from storage.
     *
     * @param int $timeout Age in seconds after which tokens expire
     * @return void
     */
    public function purge(int $timeout): void;
}
