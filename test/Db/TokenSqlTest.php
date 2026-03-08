<?php

declare(strict_types=1);

namespace Horde\Token\Test\Db;

use Horde\Token\Storage\SqlStorage;
use Horde\Token\Token;
use Horde\Token\TokenConfig;
use Horde_Test_Factory_Db;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Token facade with SQL storage
 *
 * @category Horde
 * @package  Token
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[CoversClass(Token::class)]
class TokenSqlTest extends TestCase
{
    private static $_db;
    private string $secret = 'test-secret-key-for-sql-testing';

    public static function setUpBeforeClass(): void
    {
        $factory_db = new Horde_Test_Factory_Db();

        if (class_exists('Horde_Db_Adapter_Pdo_Sqlite')) {
            self::$_db = $factory_db->create([
                'migrations' => [
                    'migrationsPath' => __DIR__ . '/../../migration/Horde/Token',
                ],
            ]);
        }
    }

    protected function setUp(): void
    {
        if (!self::$_db) {
            $this->markTestSkipped('No database available');
        }
    }

    public function testSqlFactoryCreatesTokenService(): void
    {
        $token = Token::sql($this->secret, self::$_db);

        $this->assertInstanceOf(Token::class, $token);
        $this->assertInstanceOf(SqlStorage::class, $token->getStorage());
    }

    public function testSqlFactoryWithCustomConfig(): void
    {
        $config = new TokenConfig($this->secret, 3600);
        $token = Token::sql($this->secret, self::$_db, $config);

        $this->assertSame($config, $token->getConfig());
    }

    public function testSqlFactoryWithCustomTable(): void
    {
        $token = Token::sql($this->secret, self::$_db, null, 'custom_tokens');

        $this->assertInstanceOf(Token::class, $token);
        $this->assertInstanceOf(SqlStorage::class, $token->getStorage());
    }

    public function testSqlStorageTokenLifecycle(): void
    {
        $token = Token::sql($this->secret, self::$_db);

        // Generate token
        $generated = $token->generate('test-seed');
        $this->assertNotEmpty($generated->token);

        // Validate first use (should succeed)
        $token->validateUnique($generated->token, 'test-seed');

        // Second use should throw (replay protection)
        $this->expectException(\Horde\Token\Exception\UsedTokenException::class);
        $token->validateUnique($generated->token, 'test-seed');
    }

    public function testSqlStoragePurgeWorks(): void
    {
        $token = Token::sql($this->secret, self::$_db);

        // Generate some tokens
        $token->generate('test1');
        $token->generate('test2');

        // Purge should succeed
        $token->purge();

        $this->assertTrue(true); // Assert we got here without errors
    }
}
