<?php

/**
 * PSR-0 compatibility shim for SQL-based token storage
 *
 * @deprecated Use Horde\Token\Token::sql() instead
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

use Horde\Token\Storage\SqlStorage;
use Horde\Token\Storage\TokenStorageInterface;

/**
 * SQL-based token storage (PSR-0 compatibility layer)
 *
 * @deprecated Use Horde\Token\Token::sql() instead
 * @category Horde
 * @package  Token
 */
class Horde_Token_Sql extends Horde_Token_Base
{
    /**
     * Database connection
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * PSR-4 storage
     *
     * @var SqlStorage
     */
    private $_storage;

    /**
     * Constructor
     *
     * @param array $params Configuration parameters
     * @throws Horde_Token_Exception
     */
    public function __construct($params = [])
    {
        if (!isset($params['db'])) {
            throw new Horde_Token_Exception('Missing db parameter.');
        }

        $this->_db = $params['db'];
        unset($params['db']);

        $params = array_merge([
            'table' => 'horde_tokens',
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
        $this->_storage = new SqlStorage(
            $this->_db,
            $this->_params['timeout'],
            $this->_params['table']
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
