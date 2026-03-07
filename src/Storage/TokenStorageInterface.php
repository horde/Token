<?php

declare(strict_types=1);

/**
 * Token storage interface
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

use Horde\Token\Exception\StorageException;

/**
 * Interface for token storage backends
 *
 * Provides replay protection by tracking used tokens.
 * Implementations must be thread-safe to prevent race conditions.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
interface TokenStorageInterface
{
    /**
     * Check if token exists in storage
     *
     * @param string $tokenId The token identifier
     * @return bool True if token exists (has been used)
     * @throws StorageException If storage operation fails
     */
    public function exists(string $tokenId): bool;

    /**
     * Add token to storage (mark as used)
     *
     * @param string $tokenId The token identifier
     * @return void
     * @throws StorageException If storage operation fails
     */
    public function add(string $tokenId): void;

    /**
     * Remove expired tokens from storage
     *
     * This should be called periodically to prevent storage growth.
     * The validator calls this before checking token uniqueness.
     *
     * @return void
     * @throws StorageException If storage operation fails
     */
    public function purge(): void;
}
