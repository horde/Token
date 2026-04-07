<?php

/**
 * Test the SQL based token backend.
 *
 * Copyright 2011-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Token\Test\Db;

use Horde\Token\Test\Unit\Legacy\BackendTestCase;
use Horde_Db_Adapter_Pdo_Sqlite;
use Horde_Db_Migration_Migrator;
use Horde_Token_Sql;

/**
 * Test the SQL based token backend.
 *
 * Copyright 2011-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @coversNothing
 */
class SqlTest extends BackendTestCase
{
    private static $_db;

    public static function setUpBeforeClass(): void
    {
        if (class_exists('Horde_Db_Adapter_Pdo_Sqlite')) {
            self::$_db = new Horde_Db_Adapter_Pdo_Sqlite([
                'dbname' => ':memory:',
                'charset' => 'utf-8',
            ]);
            $migrator = new Horde_Db_Migration_Migrator(
                self::$_db,
                null,
                ['migrationsPath' => __DIR__ . '/../../migration/Horde/Token']
            );
            $migrator->up();
        }
    }

    public function setUp(): void
    {
        if (!isset(self::$_db)) {
            $this->markTestSkipped('Sqlite not available.');
        }
    }

    protected function _getBackend(array $params = [])
    {
        $params = array_merge(
            [
                'secret' => 'abc',
                'db' => self::$_db,
            ],
            $params
        );
        return new Horde_Token_Sql($params);
    }
}
