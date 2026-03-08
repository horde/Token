<?php

/**
 * PSR-0 compatibility shim for Horde_Token_Base
 *
 * This class provides backward compatibility with the PSR-0 API
 * by delegating to the new PSR-4 implementation.
 *
 * @deprecated Use Horde\Token\Token instead
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Max Kalika <max@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Token
 */

use Horde\Token\Token;
use Horde\Token\TokenConfig;
use Horde\Token\TokenGenerator;
use Horde\Token\TokenValidator;
use Horde\Token\Storage\TokenStorageInterface;
use Horde\Token\Exception\InvalidTokenException;
use Horde\Token\Exception\ExpiredTokenException;
use Horde\Token\Exception\UsedTokenException;

/**
 * Base class for token implementations (PSR-0 compatibility layer)
 *
 * @deprecated Use Horde\Token\Token instead
 * @category Horde
 * @package  Token
 */
abstract class Horde_Token_Base
{
    /**
     * Hash of parameters
     *
     * @var array
     */
    protected $_params = [];

    /**
     * PSR-4 Token service
     *
     * @var Token
     */
    private $_psr4Token;

    /**
     * PSR-4 Generator
     *
     * @var TokenGenerator
     */
    private $_psr4Generator;

    /**
     * PSR-4 Validator
     *
     * @var TokenValidator
     */
    private $_psr4Validator;

    /**
     * Constructor
     *
     * @param array $params Configuration parameters
     * @throws Horde_Token_Exception
     */
    public function __construct($params)
    {
        if (!isset($params['secret'])) {
            throw new Horde_Token_Exception('Missing secret parameter.');
        }

        $params = array_merge([
            'token_lifetime' => -1,
            'timeout' => 86400,
        ], $params);

        $this->_params = $params;

        // Initialize PSR-4 components
        $this->_initializePsr4();
    }

    /**
     * Initialize PSR-4 components
     *
     * @return void
     */
    private function _initializePsr4()
    {
        $config = new TokenConfig(
            $this->_params['secret'],
            $this->_params['token_lifetime'],
            $this->_params['timeout']
        );

        $storage = $this->_createPsr4Storage();

        $this->_psr4Generator = new TokenGenerator($config);
        $this->_psr4Validator = new TokenValidator($config, $storage);
        $this->_psr4Token = new Token(
            $this->_psr4Generator,
            $this->_psr4Validator,
            $storage,
            $config
        );
    }

    /**
     * Create PSR-4 storage backend
     *
     * Subclasses should override this to return their specific storage
     *
     * @return TokenStorageInterface
     */
    abstract protected function _createPsr4Storage();

    /**
     * Check if token exists (delegate to subclass storage methods)
     *
     * @param string $tokenID Token ID
     * @return boolean True if exists
     * @throws Horde_Token_Exception
     */
    abstract public function exists($tokenID);

    /**
     * Add token (delegate to subclass storage methods)
     *
     * @param string $tokenID Token ID to add
     * @return void
     * @throws Horde_Token_Exception
     */
    abstract public function add($tokenID);

    /**
     * Purge expired tokens (delegate to subclass storage methods)
     *
     * @return void
     * @throws Horde_Token_Exception
     */
    abstract public function purge();

    /**
     * Verify token has not been used (PSR-0 API)
     *
     * @param string $token The token to verify
     * @return boolean True if not used
     * @throws Horde_Token_Exception
     */
    public function verify($token)
    {
        $this->purge();

        if ($this->exists($token)) {
            return false;
        }

        $this->add($token);
        return true;
    }

    /**
     * Generate a new token (PSR-0 API)
     *
     * @param string $seed Optional seed
     * @return string The token string
     */
    public function get($seed = '')
    {
        $generated = $this->_psr4Generator->generate($seed);
        return $generated->token;
    }

    /**
     * Check if token is valid (PSR-0 API)
     *
     * @param string $token The token
     * @param string $seed The seed
     * @param int|null $timeout Timeout in seconds
     * @param boolean $unique Check uniqueness
     * @return boolean True if valid
     */
    public function isValid(
        $token,
        $seed = '',
        $timeout = null,
        $unique = false
    ) {
        if ($unique) {
            try {
                $this->_psr4Validator->validateUnique($token, $seed);
                return true;
            } catch (InvalidTokenException|ExpiredTokenException|UsedTokenException) {
                return false;
            }
        }

        return $this->_psr4Validator->isValid($token, $seed, $timeout);
    }

    /**
     * Validate token (PSR-0 API)
     *
     * @param string $token The token
     * @param string $seed The seed
     * @param int|null $timeout Timeout
     * @return array [nonce, hash]
     * @throws Horde_Token_Exception_Invalid
     * @throws Horde_Token_Exception_Expired
     */
    public function validate($token, $seed = '', $timeout = null)
    {
        // Decode token to get nonce and hash first
        try {
            $decoded = Horde\Token\Internal\Encoder::decode($token);
        } catch (InvalidArgumentException $e) {
            throw new Horde_Token_Exception_Invalid(
                Horde_Token_Translation::t('We cannot verify that this request was really sent by you. It could be a malicious request. If you intended to perform this action, you can retry it now.')
            );
        }

        if (strlen($decoded) < 38) {
            throw new Horde_Token_Exception_Invalid(
                Horde_Token_Translation::t('We cannot verify that this request was really sent by you. It could be a malicious request. If you intended to perform this action, you can retry it now.')
            );
        }

        $nonce = substr($decoded, 0, 6);
        $hash = substr($decoded, 6);

        // Now validate using PSR-4 (this will throw proper exceptions)
        try {
            // Validate signature
            $expectedHash = Horde\Token\Internal\Signer::sign($nonce . $seed, $this->_params['secret']);
            if (!hash_equals($expectedHash, $hash)) {
                throw new InvalidTokenException('Invalid signature');
            }

            // Check expiration
            $timeoutToUse = $timeout ?? $this->_params['token_lifetime'];
            if ($timeoutToUse >= 0) {
                $nonceObj = Horde\Token\Internal\Nonce::fromBytes($nonce);
                $age = time() - $nonceObj->timestamp();
                if ($age >= $timeoutToUse) {
                    throw new ExpiredTokenException('Token expired');
                }
            }

            return [$nonce, $hash];
        } catch (ExpiredTokenException $e) {
            $timeoutToUse = $timeout ?? $this->_params['token_lifetime'];
            throw new Horde_Token_Exception_Expired(
                sprintf(
                    Horde_Token_Translation::t("This request cannot be completed because the link you followed or the form you submitted was only valid for %s minutes. Please try again now."),
                    floor($timeoutToUse / 60)
                )
            );
        } catch (InvalidTokenException $e) {
            throw new Horde_Token_Exception_Invalid(
                Horde_Token_Translation::t('We cannot verify that this request was really sent by you. It could be a malicious request. If you intended to perform this action, you can retry it now.')
            );
        }
    }

    /**
     * Validate unique token (PSR-0 API)
     *
     * @param string $token The token
     * @param string $seed The seed
     * @return null
     * @throws Horde_Token_Exception_Used
     */
    public function validateUnique($token, $seed = '')
    {
        if (!$this->isValid($token, $seed)) {
            throw new Horde_Token_Exception_Used(
                Horde_Token_Translation::t('This token is invalid!')
            );
        }

        if (!$this->verify($token)) {
            throw new Horde_Token_Exception_Used(
                Horde_Token_Translation::t('This token has been used before!')
            );
        }
    }

    /**
     * Get nonce (PSR-0 API)
     *
     * @return string 6-byte nonce
     */
    public function getNonce()
    {
        $nonce = Horde\Token\Internal\Nonce::generate();
        return $nonce->bytes();
    }

    /**
     * Encode remote address
     *
     * @return string Encoded address
     */
    protected function _encodeRemoteAddress()
    {
        return isset($_SERVER['REMOTE_ADDR'])
            ? base64_encode($_SERVER['REMOTE_ADDR'])
            : '';
    }
}
