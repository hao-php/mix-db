<?php

namespace Tests;

use Haoa\MixDatabase\ConnectionInterface;
use Haoa\MixDb\Database;
use Haoa\MixDb\TransactionWrapper;

class DatabaseTest extends TestCase
{
    public function testConstruct()
    {
        $this->assertInstanceOf(Database::class, $this->db);
    }

    public function testBeginTransaction()
    {
        $tx = $this->db->beginTransaction();
        $this->assertInstanceOf(TransactionWrapper::class, $tx);
        $tx->rollback();
    }

    public function testNestedTransaction()
    {
        $tx1 = $this->db->beginTransaction();
        
        try {
            $tx2 = $this->db->beginTransaction();
            
            $this->assertSame($tx1, $tx2);
            
            $tx2->rollback();
        } catch (\Exception $e) {
            $tx1->rollback();
            throw $e;
        }
    }

    public function testGetContextTx()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $contextTx = $this->db->getContextTx();
            $this->assertInstanceOf(TransactionWrapper::class, $contextTx);
            $this->assertSame($tx, $contextTx);
            
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testDelContextTx()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $this->db->delContextTx();
            $contextTx = $this->db->getContextTx();
            $this->assertNull($contextTx);
            
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testQueryLogToSqlWithNamedBindings()
    {
        $log = [
            'sql' => 'SELECT * FROM users WHERE name = :name',
            'bindings' => ['name' => 'test']
        ];
        
        $sql = Database::queryLogToSql($log);
        $this->assertStringContainsString('"test"', $sql);
    }

    public function testQueryLogToSqlWithPositionalBindings()
    {
        $log = [
            'sql' => 'SELECT * FROM users WHERE id = ? AND name = ?',
            'bindings' => [1, 'test']
        ];
        
        $sql = Database::queryLogToSql($log);
        $this->assertStringContainsString('"1"', $sql);
        $this->assertStringContainsString('"test"', $sql);
    }

    public function testQueryLogToSqlWithArrayBindings()
    {
        $log = [
            'sql' => 'SELECT * FROM users WHERE id IN (?)',
            'bindings' => [[1, 2, 3]]
        ];
        
        $sql = Database::queryLogToSql($log);
        $this->assertStringContainsString('"1","2","3"', $sql);
    }

    public function testQueryLogToSqlWithEmptyBindings()
    {
        $log = [
            'sql' => 'SELECT * FROM users',
            'bindings' => []
        ];
        
        $sql = Database::queryLogToSql($log);
        $this->assertEquals('SELECT * FROM users', $sql);
    }

    public function testQueryLogToSqlWithoutBindings()
    {
        $log = [
            'sql' => 'SELECT * FROM users'
        ];
        
        $sql = Database::queryLogToSql($log);
        $this->assertEquals('SELECT * FROM users', $sql);
    }

    public function testInsert()
    {
        $result = $this->db->insert('test_users', [
            'user_name' => 'test_insert_db',
            'email' => 'test@example.com',
            'age' => 25,
        ]);
        $this->assertNotNull($result);
    }

    public function testBatchInsert()
    {
        $list = [
            ['user_name' => 'batch1_db', 'email' => 'batch1@example.com', 'age' => 20],
            ['user_name' => 'batch2_db', 'email' => 'batch2@example.com', 'age' => 25],
        ];
        $result = $this->db->batchInsert('test_users', $list)->rowCount();
        $this->assertIsInt($result);
    }

    public function testTransactionWithCommit()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $id = $this->db->insert('test_users', [
                'user_name' => 'tx_commit_test',
                'email' => 'tx@example.com',
            ])->lastInsertId();
            
            $this->assertNotEmpty($id);
            
            $tx->commit();
            
            $user = $this->db->table('test_users')->where('id = ?', $id)->first();
            $this->assertNotNull($user);
            $this->assertEquals('tx_commit_test', $user['user_name']);
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWithRollback()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $id = $this->db->insert('test_users', [
                'user_name' => 'tx_rollback_test',
                'email' => 'tx@example.com',
            ])->lastInsertId();
            
            $this->assertNotEmpty($id);
            
            $tx->rollback();
            
            $user = $this->db->table('test_users')->where('id = ?', $id)->first();
            $this->assertFalse($user);
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWithCallback()
    {
        $callbackCalled = false;
        
        $tx = $this->db->beginTransaction();
        $tx->addCommitCallback(function () use (&$callbackCalled) {
            $callbackCalled = true;
        });
        
        try {
            $this->db->insert('test_users', [
                'user_name' => 'tx_callback_test',
                'email' => 'tx@example.com',
            ]);
            
            $tx->commit();
            
            $this->assertTrue($callbackCalled);
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionInNestedTransaction()
    {
        $tx1 = $this->db->beginTransaction();

        try {
            $tx2 = $this->db->beginTransaction();

            // 验证嵌套事务返回相同的 TransactionWrapper 实例
            $this->assertSame($tx1, $tx2);

            $this->db->insert('test_users', [
                'user_name' => 'nested_tx_test',
                'email' => 'tx@example.com',
            ]);

            $tx2->rollback();

            // 第一次没有真正被回滚
            $result = $this->db->table('test_users')->where('user_name = ?', 'nested_tx_test')->first();
            $this->assertNotEmpty($result);

            $tx1->rollback();

            // 第二次被回滚
            $result = $this->db->table('test_users')->where('user_name = ?', 'nested_tx_test')->first();
            $this->assertFalse($result);
        } catch (\Exception $e) {
            $tx1->rollback();
            throw $e;
        }
    }

    public function testTransactionContextIsolation()
    {
        $db1 = new Database(TEST_DSN, TEST_USERNAME, TEST_PASSWORD, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        
        $db2 = new Database(TEST_DSN, TEST_USERNAME, TEST_PASSWORD, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        
        $tx1 = $db1->beginTransaction();
        
        try {
            $tx2 = $db2->beginTransaction();
            
            $this->assertNotSame($tx1, $tx2);
            
            $tx2->rollback();
            $tx1->rollback();
        } catch (\Exception $e) {
            $tx1->rollback();
            $tx2->rollback();
            throw $e;
        }
    }

    public function testGetContext()
    {
        $context = Database::getContext();
        $this->assertNotNull($context);
    }

    public function testTransactionWrapperNestingLevel()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $tx2 = $this->db->beginTransaction();
            
            $this->assertSame($tx, $tx2);
            
            $tx2->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionRollbackOnException()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $this->db->insert('test_users', [
                'user_name' => 'exception_test',
                'email' => 'tx@example.com',
            ]);
            
            throw new \Exception('Test exception');
        } catch (\Exception $e) {
            $tx->rollback();
            $this->assertEquals('Test exception', $e->getMessage());
        }
    }

    public function testTransactionWithThrowable()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $this->db->insert('test_users', [
                'user_name' => 'throwable_test',
                'email' => 'tx@example.com',
            ]);
            
            throw new \Error('Test error');
        } catch (\Throwable $e) {
            $tx->rollback();
            $this->assertEquals('Test error', $e->getMessage());
        }
    }

    public function testTransactionWrapperAddCommitCallback()
    {
        $callbackCalled = false;
        
        $tx = $this->db->beginTransaction();
        $tx->addCommitCallback(function () use (&$callbackCalled) {
            $callbackCalled = true;
        });
        
        try {
            $tx->commit();
            $this->assertTrue($callbackCalled);
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperExec()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $result = $tx->exec('SELECT 1 as test_value');
            $this->assertNotNull($result);
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperRaw()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $result = $tx->raw('SELECT 1 as test_value');
            $this->assertNotNull($result);
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperInsert()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $result = $tx->insert('test_users', [
                'user_name' => 'tx_insert_test',
                'email' => 'tx@example.com',
            ]);
            $this->assertNotNull($result);
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperBatchInsert()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $list = [
                ['user_name' => 'tx_batch1', 'email' => 'tx1@example.com'],
                ['user_name' => 'tx_batch2', 'email' => 'tx2@example.com'],
            ];
            $result = $tx->batchInsert('test_users', $list)->rowCount();
            $this->assertIsInt($result);
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperTable()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $result = $tx->table('test_users');
            $this->assertNotNull($result);
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperDebug()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $debugCalled = false;
            
            $tx->debug(function (ConnectionInterface $conn) use (&$debugCalled) {
                $debugCalled = true;
            });
            
            $tx->table('test_users')->first();
            $this->assertTrue($debugCalled);
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperSetLogger()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $logger = new class implements \Haoa\MixDatabase\LoggerInterface {
                public function trace(float $time, string $sql, array $bindings, int $rowCount, ?\Throwable $exception): void
                {
                    // No-op
                }
            };
            
            // 验证匿名类实现了 LoggerInterface
            $this->assertInstanceOf(\Haoa\MixDatabase\LoggerInterface::class, $logger);
            
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperUpdate()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $id = $tx->insert('test_users', [
                'user_name' => 'tx_update_test',
                'email' => 'tx@example.com',
            ])->lastInsertId();
            
            $result = $tx->table('test_users')->where('id = ?', $id)->update('user_name', 'updated_tx')->rowCount();
            $this->assertIsInt($result);
            $this->assertEquals(1, $result);
            
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperUpdates()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $id = $tx->insert('test_users', [
                'user_name' => 'tx_updates_test',
                'email' => 'tx@example.com',
            ])->lastInsertId();
            
            $result = $tx->table('test_users')->where('id = ?', $id)->updates([
                'user_name' => 'updated_tx',
                'email' => 'updated@example.com',
            ])->rowCount();
            $this->assertIsInt($result);
            $this->assertEquals(1, $result);
            
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperDelete()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $id = $tx->insert('test_users', [
                'user_name' => 'tx_delete_test',
                'email' => 'tx@example.com',
            ])->lastInsertId();
            
            $result = $tx->table('test_users')->where('id = ?', $id)->delete()->rowCount();
            $this->assertIsInt($result);
            $this->assertEquals(1, $result);
            
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperGet()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $tx->insert('test_users', [
                'user_name' => 'tx_get_test',
                'email' => 'tx@example.com',
            ]);
            
            $result = $tx->table('test_users')->get();
            $this->assertIsArray($result);
            $this->assertCount(1, $result);
            
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperFirst()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $tx->insert('test_users', [
                'user_name' => 'tx_first_test',
                'email' => 'tx@example.com',
            ]);
            
            $result = $tx->table('test_users')->first();
            $this->assertIsArray($result);
            $this->assertEquals('tx_first_test', $result['user_name']);
            
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testTransactionWrapperComplexQuery()
    {
        $tx = $this->db->beginTransaction();
        
        try {
            $tx->insert('test_users', [
                'user_name' => 'complex_tx1',
                'age' => 20,
            ]);
            $tx->insert('test_users', [
                'user_name' => 'complex_tx2',
                'age' => 25,
            ]);
            
            $result = $tx->table('test_users')
                ->where('age > ?', 18)
                ->where('user_name LIKE ?', '%complex%')
                ->order('age', 'desc')
                ->limit(10)
                ->get();
            
            $this->assertIsArray($result);
            $this->assertCount(2, $result);
            
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

}
