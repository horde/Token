<?php

declare(strict_types=1);

/**
 * Null token storage (no-op)
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
 * Null storage implementation (no replay protection)
 *
 * This storage backend does nothing - it never stores or checks tokens.
 * Useful when replay protection is not needed or handled elsewhere.
 *
 * WARNING: Using NullStorage disables replay attack protection!
 * Only use when you understand the security implications.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
final class NullStorage implements TokenStorageInterface
{
    /**
     * Always returns false (token never exists)
     *
     * @param string $tokenId The token identifier
     * @return bool Always false
     */
    public function exists(string $tokenId): bool
    {
        return false;
    }

    /**
     * Does nothing (no-op)
     *
     * @param string $tokenId The token identifier
     * @return void
     */
    public function add(string $tokenId): void
    {
        // No-op
    }

    /**
     * Does nothing (no-op)
     *
     * @return void
     */
    public function purge(): void
    {
        // No-op
    }
}
