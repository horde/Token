<?php

/**
 * PSR-0 compatibility shim for null token storage
 *
 * @deprecated Use Horde\Token\Token::null() instead
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Token
 */

use Horde\Token\Storage\NullStorage;
use Horde\Token\Storage\TokenStorageInterface;

/**
 * Null token storage (PSR-0 compatibility layer)
 *
 * @deprecated Use Horde\Token\Token::null() instead
 * @category Horde
 * @package  Token
 */
class Horde_Token_Null extends Horde_Token_Base
{
    /**
     * PSR-4 storage
     *
     * @var NullStorage
     */
    private $_storage;

    /**
     * Create PSR-4 storage backend
     *
     * @return TokenStorageInterface
     */
    protected function _createPsr4Storage()
    {
        $this->_storage = new NullStorage();
        return $this->_storage;
    }

    /**
     * Check if token exists (always false)
     *
     * @param string $tokenID Token ID
     * @return boolean Always false
     */
    public function exists($tokenID)
    {
        return $this->_storage->exists($tokenID);
    }

    /**
     * Add token (no-op)
     *
     * @param string $tokenID Token ID
     * @return void
     */
    public function add($tokenID)
    {
        $this->_storage->add($tokenID);
    }

    /**
     * Purge expired tokens (no-op)
     *
     * @return void
     */
    public function purge()
    {
        $this->_storage->purge();
    }
}
