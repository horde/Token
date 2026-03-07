<?php

/**
 * PSR-0 compatibility shim for Horde_Token
 *
 * @deprecated Use Horde\Token\Token instead
 *
 * Copyright 1999-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Max Kalika <max@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Token
 */

use Horde\Token\TokenConfig;
use Horde\Token\TokenGenerator;

/**
 * Static token utility class (PSR-0 compatibility layer)
 *
 * @deprecated Use Horde\Token\Token instead
 * @category Horde
 * @package  Token
 */
class Horde_Token
{
    /**
     * Generate a connection ID (deprecated)
     *
     * @deprecated Use Horde\Token\Token facade instead
     *
     * @param string $seed Optional seed
     * @return string Generated ID
     */
    public static function generateId($seed = '')
    {
        // Use PSR-4 generator with temporary secret
        $config = TokenConfig::default('deprecated-static-method');
        $generator = new TokenGenerator($config);
        $token = $generator->generate($seed);

        return $token->token;
    }
}
