<?php
/**
 * Test the SQL based token backend.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
namespace Horde\Token\Unit;
use Horde\Token\BackendTestCase as BackendTestCase;
use \Horde_Test_Factory_Db;

/**
 * Test the SQL based token backend.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class SqlTest extends BackendTestCase
{
    private static $_db;

    public static function setUpBeforeClass(): void
    {
        $factory_db = new Horde_Test_Factory_Db();

        if (class_exists('Horde_Db_Adapter_Pdo_Sqlite')) {
            self::$_db = $factory_db->create(array(
                'migrations' => array(
                    'migrationsPath' => __DIR__ . '/../../../../migration/Horde/Token'
                )
            ));
        } 
    }

    public function setUp(): void
    {
        if (!isset(self::$_db)) {
            $this->markTestSkipped('Sqlite not available.');
        }
    }

    protected function _getBackend(array $params = array())
    {
        $params = array_merge(
            array(
                'secret' => 'abc',
                'db' => self::$_db
            ),
            $params
        );
        return new Horde_Token_Sql($params);
    }

}
