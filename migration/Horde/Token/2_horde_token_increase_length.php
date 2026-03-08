<?php
/**
 * Increase token_id column length for new token format
 *
 * The PSR-4 refactored token generator creates tokens that are 51 characters
 * long (Base64URL encoded nonce + HMAC-SHA256 signature). The original schema
 * only allowed 32 characters, which would cause truncation.
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

class HordeTokenIncreaseLength extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_tokens', 'token_id', 'string', ['limit' => 64, 'null' => false]);
    }

    public function down()
    {
        $this->changeColumn('horde_tokens', 'token_id', 'string', ['limit' => 32, 'null' => false]);
    }
}
