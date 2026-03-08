<?php

declare(strict_types=1);

/**
 * Internal base64 URL-safe encoding
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

use InvalidArgumentException;

/**
 * Base64 URL-safe encoding/decoding
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @internal
 */
final class Encoder
{
    /**
     * Encode to base64 URL-safe format
     *
     * @param string $data Raw binary data
     * @return string Base64 URL-safe encoded string
     */
    public static function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decode from base64 URL-safe format
     *
     * @param string $encoded Base64 URL-safe encoded string
     * @return string Raw binary data
     */
    public static function decode(string $encoded): string
    {
        $padded = str_pad(
            strtr($encoded, '-_', '+/'),
            (int) (ceil(strlen($encoded) / 4) * 4),
            '=',
            STR_PAD_RIGHT
        );

        $decoded = base64_decode($padded, strict: true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64 encoding');
        }

        return $decoded;
    }
}
