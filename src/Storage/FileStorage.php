<?php

declare(strict_types=1);

/**
 * File-based token storage
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
 * File-based token storage implementation
 *
 * Stores used tokens in files named by remote address.
 * Each file contains one token per line (base64 encoded).
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
final class FileStorage implements TokenStorageInterface
{
    /**
     * @param string $tokenDir Directory for token files
     * @param int $timeout Token timeout for purging (seconds)
     */
    public function __construct(
        private readonly string $tokenDir,
        private readonly int $timeout
    ) {
        if (!is_dir($tokenDir)) {
            throw new StorageException("Token directory does not exist: {$tokenDir}");
        }

        if (!is_writable($tokenDir)) {
            throw new StorageException("Token directory is not writable: {$tokenDir}");
        }
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
        $file = $this->getFilePath();

        if (!file_exists($file)) {
            return false;
        }

        $encoded = base64_encode($tokenId);
        $contents = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($contents === false) {
            throw new StorageException("Failed to read token file: {$file}");
        }

        return in_array($encoded, $contents, strict: true);
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
        $file = $this->getFilePath();
        $encoded = base64_encode($tokenId) . "\n";

        $result = @file_put_contents($file, $encoded, FILE_APPEND | LOCK_EX);

        if ($result === false) {
            throw new StorageException("Failed to write token file: {$file}");
        }
    }

    /**
     * Remove expired tokens from storage
     *
     * Deletes files older than the configured timeout.
     *
     * @return void
     * @throws StorageException If storage operation fails
     */
    public function purge(): void
    {
        $pattern = $this->tokenDir . '/horde_token_*';
        $cutoff = time() - $this->timeout;

        $files = glob($pattern);
        if ($files === false) {
            throw new StorageException("Failed to list token files in: {$this->tokenDir}");
        }

        foreach ($files as $file) {
            $mtime = @filemtime($file);
            if ($mtime === false) {
                continue; // File may have been deleted by another process
            }

            if ($mtime < $cutoff) {
                @unlink($file); // Ignore errors (file may already be deleted)
            }
        }
    }

    /**
     * Get file path for current remote address
     *
     * @return string Full path to token file
     */
    private function getFilePath(): string
    {
        $address = $this->getRemoteAddress();
        $encoded = base64_encode($address);
        return $this->tokenDir . '/horde_token_' . $encoded;
    }

    /**
     * Get remote address from server variables
     *
     * @return string Remote address or 'unknown'
     */
    private function getRemoteAddress(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
