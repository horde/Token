<?php

declare(strict_types=1);

/**
 * Internal HMAC signing
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @internal
 */

namespace Horde\Token\Internal;

/**
 * HMAC-SHA256 signing for tokens
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @internal
 */
final class Signer
{
    /**
     * Sign data with HMAC-SHA256
     *
     * @param string $data Data to sign
     * @param string $secret Secret key
     * @return string Raw binary signature (32 bytes)
     */
    public static function sign(string $data, string $secret): string
    {
        return hash('sha256', $data . $secret, binary: true);
    }
}
