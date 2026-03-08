<?php

class HordeTokenBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_tokens', $this->tables())) {
            $t = $this->createTable('horde_tokens', ['autoincrementKey' => ['token_address', 'token_id']]);
            $t->column('token_address', 'string', ['limit' => 100, 'null' => false]);
            $t->column('token_id', 'string', ['limit' => 32, 'null' => false]);
            $t->column('token_timestamp', 'bigint', ['null' => false]);
            $t->end();
        }
    }

    public function down()
    {
        $this->dropTable('horde_tokens');
    }
}
