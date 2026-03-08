<?php

/**
 * PSR-0 compatibility shim for file-based token storage
 *
 * @deprecated Use Horde\Token\Token::file() instead
 *
 * Copyright 1999-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Max Kalika <max@horde.org>
 * @category Horde
 * @package  Token
 */

use Horde\Token\Storage\FileStorage;
use Horde\Token\Storage\TokenStorageInterface;

/**
 * File-based token storage (PSR-0 compatibility layer)
 *
 * @deprecated Use Horde\Token\Token::file() instead
 * @category Horde
 * @package  Token
 */
class Horde_Token_File extends Horde_Token_Base
{
    /**
     * PSR-4 storage
     *
     * @var FileStorage
     */
    private $_storage;

    /**
     * Constructor
     *
     * @param array $params Configuration parameters
     */
    public function __construct($params = [])
    {
        $params = array_merge([
            'token_dir' => sys_get_temp_dir(),
        ], $params);

        parent::__construct($params);
    }

    /**
     * Create PSR-4 storage backend
     *
     * @return TokenStorageInterface
     */
    protected function _createPsr4Storage()
    {
        $this->_storage = new FileStorage(
            $this->_params['token_dir'],
            $this->_params['timeout']
        );
        return $this->_storage;
    }

    /**
     * Check if token exists
     *
     * @param string $tokenID Token ID
     * @return boolean True if exists
     * @throws Horde_Token_Exception
     */
    public function exists($tokenID)
    {
        try {
            return $this->_storage->exists($tokenID);
        } catch (Horde\Token\Exception\StorageException $e) {
            throw new Horde_Token_Exception($e->getMessage(), 0, $e);
        }
    }

    /**
     * Add token
     *
     * @param string $tokenID Token ID
     * @return void
     * @throws Horde_Token_Exception
     */
    public function add($tokenID)
    {
        try {
            $this->_storage->add($tokenID);
        } catch (Horde\Token\Exception\StorageException $e) {
            throw new Horde_Token_Exception($e->getMessage(), 0, $e);
        }
    }

    /**
     * Purge expired tokens
     *
     * @return void
     * @throws Horde_Token_Exception
     */
    public function purge()
    {
        try {
            $this->_storage->purge();
        } catch (Horde\Token\Exception\StorageException $e) {
            throw new Horde_Token_Exception($e->getMessage(), 0, $e);
        }
    }
}
