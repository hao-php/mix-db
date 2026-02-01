<?php

namespace Tests;

use Tests\Fixtures\TestUserModel;

class ModelTest extends TestCase
{
    public function testWhere()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->where('user_name', 'test_user');
        $sql = $model->getLastSql();
        $this->assertStringContainsString('WHERE', $sql);
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

    public function testUpdateMultiple()
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

    public function testGetAll()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->insertGetId(['user_name' => 'get_test']);
        
        $result = $model->get();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testGetFirst()
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

    public function testGetValue()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->insertGetId(['user_name' => 'value_test']);
        
        $value = $model->value('user_name');
        $this->assertEquals('value_test', $value);
    }

    public function testGetColumn()
    {
        $model = TestUserModel::newInstance($this->db);
        $model->insertGetId(['user_name' => 'column_test']);
        
        $column = $model->column('user_name');
        $this->assertIsArray($column);
        $this->assertCount(1, $column);
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

}
