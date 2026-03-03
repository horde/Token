<?php

declare(strict_types=1);

/**
 * SQL-based token storage
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
use Horde_Db_Adapter;
use Horde_Db_Exception;

/**
 * SQL-based token storage implementation
 *
 * Stores tokens in a SQL database using Horde_Db adapter.
 *
 * Required table schema:
 * <code>
 * CREATE TABLE horde_tokens (
 *     token_address    VARCHAR(100) NOT NULL,
 *     token_id         VARCHAR(32) NOT NULL,
 *     token_timestamp  BIGINT NOT NULL,
 *     PRIMARY KEY (token_address, token_id)
 * );
 * </code>
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
final class SqlStorage implements TokenStorageInterface
{
    /**
     * @param Horde_Db_Adapter $db Database adapter
     * @param int $timeout Token timeout for purging (seconds)
     * @param string $table Table name
     */
    public function __construct(
        private readonly Horde_Db_Adapter $db,
        private readonly int $timeout,
        private readonly string $table = 'horde_tokens'
    ) {
    }

    /**
     * Check if token exists in storage
     *
     * @param string $tokenId The token identifier
     * @return bool True if token exists
     * @throws StorageException If storage operation fails
     */
    public function exists(string $tokenId): bool
    {
        $query = "SELECT token_id FROM {$this->table}
                  WHERE token_address = ? AND token_id = ?";

        $address = $this->getRemoteAddress();

        try {
            $result = $this->db->selectValue($query, [$address, $tokenId]);
            return $result !== null && $result !== false;
        } catch (Horde_Db_Exception $e) {
            // Treat query errors as "not found" for exists check
            return false;
        }
    }

    /**
     * Add token to storage
     *
     * @param string $tokenId The token identifier
     * @return void
     * @throws StorageException If storage operation fails
     */
    public function add(string $tokenId): void
    {
        $query = "INSERT INTO {$this->table}
                  (token_address, token_id, token_timestamp)
                  VALUES (?, ?, ?)";

        $address = $this->getRemoteAddress();
        $timestamp = time();

        try {
            $this->db->insert($query, [$address, $tokenId, $timestamp]);
        } catch (Horde_Db_Exception $e) {
            throw new StorageException(
                "Failed to add token to database: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Remove expired tokens from storage
     *
     * Deletes tokens older than the configured timeout.
     *
     * @return void
     * @throws StorageException If storage operation fails
     */
    public function purge(): void
    {
        $query = "DELETE FROM {$this->table} WHERE token_timestamp < ?";
        $cutoff = time() - $this->timeout;

        try {
            $this->db->delete($query, [$cutoff]);
        } catch (Horde_Db_Exception $e) {
            throw new StorageException(
                "Failed to purge tokens from database: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get remote address from server variables
     *
     * @return string Base64-encoded remote address
     */
    private function getRemoteAddress(): string
    {
        $address = $_SERVER['REMOTE_ADDR'] ?? '';
        return base64_encode($address);
    }
}
