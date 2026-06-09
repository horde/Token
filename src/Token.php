<?php

declare(strict_types=1);

/**
 * Token service facade
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

use Horde\Token\Storage\FileStorage;
use Horde\Token\Storage\NullStorage;
use Horde\Token\Storage\SqlStorage;
use Horde\Token\Storage\TokenStorageInterface;
use Horde_Db_Adapter;

/**
 * CSRF token service
 *
 * Main facade for generating and validating CSRF tokens.
 * Provides factory methods for common storage backends.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
final class Token
{
    /**
     * @param TokenGenerator $generator Token generator
     * @param TokenValidator $validator Token validator
     * @param TokenStorageInterface $storage Token storage backend
     * @param TokenConfig $config Token configuration
     */
    public function __construct(
        private readonly TokenGenerator $generator,
        private readonly TokenValidator $validator,
        private readonly TokenStorageInterface $storage,
        private readonly TokenConfig $config
    ) {}

    /**
     * Create token service with file storage
     *
     * @param string $secret Secret key for signing tokens
     * @param string $tokenDir Directory for token files
     * @param TokenConfig|null $config Optional custom configuration
     * @return self
     *
     * @example
     * $token = Token::file('my-secret', '/tmp/tokens');
     * $generated = $token->generate('form_id');
     */
    public static function file(
        string $secret,
        string $tokenDir,
        ?TokenConfig $config = null
    ): self {
        $config ??= TokenConfig::default($secret);
        $storage = new FileStorage($tokenDir, $config->timeout);

        return new self(
            new TokenGenerator($config),
            new TokenValidator($config, $storage),
            $storage,
            $config
        );
    }

    /**
     * Create token service with SQL storage
     *
     * @param string $secret Secret key for signing tokens
     * @param Horde_Db_Adapter $db Database adapter
     * @param TokenConfig|null $config Optional custom configuration
     * @param string $table Table name (default: 'horde_tokens')
     * @return self
     *
     * @example
     * $token = Token::sql('my-secret', $db);
     * $generated = $token->generate('delete_user');
     */
    public static function sql(
        string $secret,
        Horde_Db_Adapter $db,
        ?TokenConfig $config = null,
        string $table = 'horde_tokens'
    ): self {
        $config ??= TokenConfig::default($secret);
        $storage = new SqlStorage($db, $config->timeout, $table);

        return new self(
            new TokenGenerator($config),
            new TokenValidator($config, $storage),
            $storage,
            $config
        );
    }

    /**
     * Create token service with null storage (no replay protection)
     *
     * WARNING: This disables replay attack protection!
     * Only use when replay protection is not needed.
     *
     * @param string $secret Secret key for signing tokens
     * @param TokenConfig|null $config Optional custom configuration
     * @return self
     *
     * @example
     * $token = Token::null('my-secret');
     * $generated = $token->generate('api_request');
     */
    public static function null(
        string $secret,
        ?TokenConfig $config = null
    ): self {
        $config ??= TokenConfig::default($secret);
        $storage = new NullStorage();

        return new self(
            new TokenGenerator($config),
            new TokenValidator($config, $storage),
            $storage,
            $config
        );
    }

    /**
     * Generate a new CSRF token
     *
     * Pass a per-call $secret to sign with a different key without
     * rebuilding the Token service. This is the preferred way to handle
     * multiple secrets within one request (e.g., a service iterating
     * sessions or composing tokens for several identities). Constructing
     * a new Token per secret is feasible but wasteful — storage and
     * config are invariant across calls.
     *
     * @param string      $seed   Optional seed for context binding
     * @param string|null $secret Per-call secret override; null uses the
     *                            configured secret.
     * @return GeneratedToken The generated token
     *
     * @example
     * $token = $service->generate('checkout_form');
     * echo $token->token;  // Use in form
     *
     * @example
     * // Per-call secret without rebuilding the Token service
     * $token = $service->generate('checkout_form', $session->getSecret());
     */
    public function generate(string $seed = '', ?string $secret = null): GeneratedToken
    {
        return $this->generator->generate($seed, $secret);
    }

    /**
     * Check if token is valid (non-throwing version)
     *
     * @param string      $token   The token to validate
     * @param string      $seed    The seed used during generation
     * @param int|null    $timeout Custom timeout (null = use config)
     * @param string|null $secret  Per-call secret override; null uses the
     *                             configured secret. See {@see generate()}
     *                             for the rationale.
     * @return bool True if token is valid
     *
     * @example
     * if ($service->isValid($_POST['token'], 'form_id')) {
     *     // Process form
     * }
     */
    public function isValid(
        string $token,
        string $seed = '',
        ?int $timeout = null,
        ?string $secret = null
    ): bool {
        return $this->validator->isValid($token, $seed, $timeout, $secret);
    }

    /**
     * Validate token and mark as used (one-time use)
     *
     * @param string      $token  The token to validate
     * @param string      $seed   The seed used during generation
     * @param string|null $secret Per-call secret override; null uses the
     *                            configured secret. See {@see generate()}
     *                            for the rationale.
     * @return void
     * @throws Exception\InvalidTokenException If signature is invalid
     * @throws Exception\ExpiredTokenException If token has expired
     * @throws Exception\UsedTokenException If token was already used
     *
     * @example
     * try {
     *     $service->validateUnique($_POST['token'], 'delete_user');
     *     // Token valid, proceed with deletion
     * } catch (Exception\TokenException $e) {
     *     // Token invalid
     * }
     */
    public function validateUnique(string $token, string $seed = '', ?string $secret = null): void
    {
        $this->validator->validateUnique($token, $seed, $secret);
    }

    /**
     * Clean up expired tokens
     *
     * @return void
     */
    public function purge(): void
    {
        $this->storage->purge();
    }

    /**
     * Get the configuration
     *
     * @return TokenConfig
     */
    public function getConfig(): TokenConfig
    {
        return $this->config;
    }

    /**
     * Get the storage backend
     *
     * @return TokenStorageInterface
     */
    public function getStorage(): TokenStorageInterface
    {
        return $this->storage;
    }
}
