<?php

namespace Tests;

use Tests\Fixtures\TestUserModel;

class ModelTest extends TestCase
{
    public function testConstruct()
    {
        $model = TestUserModel::newInstance($this->db);
        $this->assertInstanceOf(TestUserModel::class, $model);
    }

    public function testGetTable()
    {
        $model = TestUserModel::newInstance($this->db);
        $this->assertEquals('test_users', $model->getTable());
    }

    public function testSetTable()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->setTable('other_table');
        $this->assertEquals('other_table', $model->getTable());
    }

    public function testAlias()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->alias('u');
        $this->assertEquals('u', $model->alias);
    }

    public function testWhere()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->where('user_name', 'test_user');
        $this->assertCount(1, $model->wheres);
    }

    public function testWhereWithOperator()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->where('age', '>', 18);
        $this->assertCount(1, $model->wheres);
    }

    public function testWhereWithArray()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->where([
            ['user_name', '=', 'test_user'],
            ['age', '>', 18]
        ]);
        $this->assertCount(1, $model->wheres);
    }

    public function testWhereRaw()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->whereRaw('user_name LIKE ?', '%test%');
        $this->assertCount(1, $model->wheres);
    }

    public function testWhereOr()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->orWhere('user_name', 'test_user');
        $this->assertCount(1, $model->ors);
    }

    public function testOffset()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->offset(10);
        $this->assertEquals(10, $model->offset);
    }

    public function testLimit()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->limit(20);
        $this->assertEquals(20, $model->limit);
    }

    public function testPage()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->page(2, 10);
        $this->assertEquals(10, $model->offset);
        $this->assertEquals(10, $model->limit);
    }

    public function testSelect()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->select('id, user_name');
        $this->assertEquals('id, user_name', $model->fields);
    }

    public function testHaving()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->having('count', '>', 5);
        $this->assertCount(1, $model->havings);
    }

    public function testHavingRaw()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->havingRaw('COUNT(*) > ?', 5);
        $this->assertCount(1, $model->havings);
    }

    public function testGroup()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->group('user_name');
        $this->assertCount(1, $model->group);
    }

    public function testOrder()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->order('created_at', 'DESC');
        $this->assertCount(1, $model->orders);
    }

    public function testInsert()
    {
        $model = TestUserModel::newInstance($this->db);
        $result = $model->insert([
            'user_name' => 'test_insert',
            'email' => 'test@example.com',
            'age' => 25,
        ]);
        $this->assertNotNull($result);
    }

    public function testInsertGetId()
    {
        $model = TestUserModel::newInstance($this->db);
        $id = $model->insertGetId([
            'user_name' => 'test_insert_id',
            'email' => 'test@example.com',
            'age' => 25,
        ]);
        $this->assertIsString($id);
        $this->assertNotEmpty($id);
    }

    public function testBatchInsert()
    {
        $model = TestUserModel::newInstance($this->db);
        $list = [
            ['user_name' => 'batch1', 'email' => 'batch1@example.com', 'age' => 20],
            ['user_name' => 'batch2', 'email' => 'batch2@example.com', 'age' => 25],
        ];
        $result = $model->batchInsert($list);
        $this->assertIsInt($result);
    }

    public function testUpdate()
    {
        $model = TestUserModel::newInstance($this->db);
        $id = $model->insertGetId(['user_name' => 'update_test']);
        
        $result = $model->where('id', $id)->update('user_name', 'updated_name');
        $this->assertIsInt($result);
        $this->assertEquals(1, $result);
    }

    public function testUpdates()
    {
        $model = TestUserModel::newInstance($this->db);
        $id = $model->insertGetId(['user_name' => 'updates_test']);
        
        $result = $model->where('id', $id)->updates([
            'user_name' => 'updated_name',
            'email' => 'updated@example.com',
        ]);
        $this->assertIsInt($result);
        $this->assertEquals(1, $result);
    }

    public function testDelete()
    {
        $model = TestUserModel::newInstance($this->db);
        $id = $model->insertGetId(['user_name' => 'delete_test']);
        
        $result = $model->where('id', $id)->delete();
        $this->assertIsInt($result);
        $this->assertEquals(1, $result);
    }

    public function testGet()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->insertGetId(['user_name' => 'get_test']);
        
        $result = $model->get();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testFirst()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->insertGetId(['user_name' => 'first_test']);
        
        $result = $model->first();
        $this->assertIsArray($result);
        $this->assertEquals('first_test', $result['user_name']);
    }

    public function testCount()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->insertGetId(['user_name' => 'count_test']);
        
        $count = $model->count();
        $this->assertIsInt($count);
        $this->assertEquals(1, $count);
    }

    public function testValue()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->insertGetId(['user_name' => 'value_test']);
        
        $value = $model->value('user_name');
        $this->assertEquals('value_test', $value);
    }

    public function testColumn()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->insertGetId(['user_name' => 'column_test']);
        
        $column = $model->column('user_name');
        $this->assertIsArray($column);
        $this->assertCount(1, $column);
    }

    public function testGetLastQueryLog()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->first();
        
        $log = $model->getLastQueryLog();
        $this->assertIsArray($log);
        $this->assertArrayHasKey('sql', $log);
    }

    public function testGetLastSql()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->first();
        
        $sql = $model->getLastSql();
        $this->assertIsString($sql);
        $this->assertStringContainsString('SELECT', $sql);
    }

    public function testGetLastConnectionType()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->first();
        
        $type = $model->getLastConnectionType();
        $this->assertIsString($type);
    }

    public function testReadConnectionType()
    {
        $model = TestUserModel::newInstance($this->db, $this->readDb);
        $model->first();
        
        $type = $model->getLastConnectionType();
        $this->assertEquals('read', $type);
    }

    public function testWriteConnectionType()
    {
        $model = TestUserModel::newInstance($this->db, $this->readDb);
        $model->insertGetId(['user_name' => 'write_test']);
        
        $type = $model->getLastConnectionType();
        $this->assertEquals('default', $type);
    }

    public function testTransactionConnectionType()
    {
        $model = TestUserModel::newInstance($this->db);
        $tx = $this->db->beginTransaction();
        
        try {
            $model->insertGetId(['user_name' => 'tx_test']);
            $type = $model->getLastConnectionType();
            $this->assertEquals('transaction', $type);
            $tx->rollback();
        } catch (\Exception $e) {
            $tx->rollback();
            throw $e;
        }
    }

    public function testDebug()
    {
        $model = TestUserModel::newInstance($this->db);
        $debugCalled = false;
        
        $model->debug(function ($sql, $bindings, $time) use (&$debugCalled) {
            $debugCalled = true;
        });
        
        $model->first();
        $this->assertTrue($debugCalled);
    }

    public function testJoin()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->join('other_table', 'other_table.user_id = test_users.id');
        $this->assertCount(1, $model->joins);
    }

    public function testLeftJoin()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->leftJoin('other_table', 'other_table.user_id = test_users.id');
        $this->assertCount(1, $model->leftJoins);
    }

    public function testComplexQuery()
    {
        $model = TestUserModel::newInstance($this->db);
        
        $model->insertGetId(['user_name' => 'complex1', 'age' => 20]);
        $model->insertGetId(['user_name' => 'complex2', 'age' => 25]);
        
        $result = $model
            ->where('age', '>', 18)
            ->where('user_name', 'LIKE', '%complex%')
            ->order('age', 'DESC')
            ->limit(10)
            ->get();
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testUpdateWithAutoTimestamp()
    {
        $model = TestUserModel::newInstance($this->db);
        $id = $model->insertGetId(['user_name' => 'timestamp_test']);
        
        sleep(1);
        
        $result = $model->where('id', $id)->update('user_name', 'updated_timestamp');
        $this->assertIsInt($result);
        
        $user = $model->where('id', $id)->first();
        $this->assertNotNull($user['updated_at']);
    }

    public function testInsertWithAutoTimestamp()
    {
        $model = TestUserModel::newInstance($this->db);
        $id = $model->insertGetId(['user_name' => 'auto_timestamp_test']);
        
        $user = $model->where('id', $id)->first();
        $this->assertNotNull($user['created_at']);
        $this->assertNotNull($user['updated_at']);
    }

    public function testWhereException()
    {
        $model = TestUserModel::newInstance($this->db);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('where格式错误');
        
        $model->where('invalid_format');
    }

    public function testUpdateWithoutWhereException()
    {
        $model = TestUserModel::newInstance($this->db);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('update操作必须带条件');
        
        $model->update('user_name', 'test');
    }

    public function testDeleteWithoutWhereException()
    {
        $model = TestUserModel::newInstance($this->db);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('delete操作必须带条件');
        
        $model->delete();
    }

    public function testDatabaseNotSetException()
    {
        $model = new class extends TestUserModel {
            public function testGetConn()
            {
                return $this->getConn();
            }
        };
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Database connection is not set');
        
        $model->testGetConn();
    }

    public function testTableNotSetException()
    {
        $model = new class extends TestUserModel {
            public static string $tableName = '';
            
            public function testGetConn()
            {
                return $this->getConn();
            }
        };
        
        $model->database = $this->db;
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Table name is not set');
        
        $model->testGetConn();
    }
}
