<?php
/**
 * Token tracking implementation for local files.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Max Kalika <max@horde.org>
 * @category Horde
 * @package  Token
 */
class Horde_Token_File extends Horde_Token_Driver
{
    /**
     * Handle for the open file descriptor.
     *
     * @var resource
     */
    protected $_fd = false;

    /**
     * Boolean indicating whether or not we have an open file descriptor.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'timeout' - (integer) The period (in seconds) after which an id is
     *             purged.
     *             DEFAULT: 86400 (24 hours)
     * 'token_dir' - (string)  The directory where to keep token files.
     *               DEFAULT: System temporary directory
     * </pre>
     */
    public function __construct($params = array())
    {
        $params = array_merge(array(
            'timeout' => 86400,
            'token_dir' => Horde_Util::getTempDir()
        ), $params);

        parent::__construct($params);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->_disconnect(false);
    }

    /**
     * Delete all expired connection IDs.
     *
     * @throws Horde_Token_Exception
     */
    public function purge()
    {
        // Make sure we have no open file descriptors before unlinking
        // files.
        $this->_disconnect();

        /* Build stub file list. */
        if (!($dir = opendir($this->_params['token_dir']))) {
            throw new Horde_Token_Exception('Unable to open token directory');
        }

        /* Find expired stub files */
        while (($dirEntry = readdir($dir)) != '') {
            if (preg_match('|^conn_\w{8}$|', $dirEntry) && (time() - filemtime($this->_params['token_dir'] . '/' . $dirEntry) >= $this->_params['timeout']) &&
                !@unlink($this->_params['token_dir'] . '/' . $dirEntry)) {
                throw new Horde_Token_Exception('Unable to purge token file.');
            }
        }

        closedir($dir);
    }

    /**
     * Does the token exist?
     *
     * @param string $tokenID  Token ID.
     *
     * @return boolean  True if the token exists.
     * @throws Horde_Token_Exception
     */
    public function exists($tokenID)
    {
        $this->_connect();

        /* Find already used IDs. */
        $fileContents = file($this->_params['token_dir'] . '/conn_' . $this->_encodeRemoteAddress());
        if ($fileContents) {
            for ($i = 0, $iMax = count($fileContents); $i < $iMax; ++$i) {
                if (chop($fileContents[$i]) == $tokenID) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add a token ID.
     *
     * @param string $tokenID  Token ID to add.
     *
     * @throws Horde_Token_Exception
     */
    public function add($tokenID)
    {
        $this->_connect();

        /* Write the entry. */
        fwrite($this->_fd, $tokenID . "\n");

        $this->_disconnect();
    }

    /**
     * Opens a file descriptor to a new or existing file.
     *
     * @throws Horde_Token_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return;
        }

        // Open a file descriptor to the token stub file.
        $this->_fd = @fopen($this->_params['token_dir'] . '/conn_' . $this->_encodeRemoteAddress(), 'a');
        if (!$this->_fd) {
            throw new Horde_Token_Exception('Failed to open token file.');
        }

        $this->_connected = true;
    }

    /**
     * Closes the file descriptor.
     *
     * @param boolean $error  Throw exception on error?
     *
     * @throws Horde_Token_Exception
     */
    protected function _disconnect($error = true)
    {
        if ($this->_connected) {
            $this->_connected = false;
            if (!fclose($this->_fd) && $error) {
                throw new Horde_Token_Exception('Unable to close file descriptors');
            }
        }
    }

}
