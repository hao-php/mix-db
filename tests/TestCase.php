<?php

namespace Tests;

use Haoa\MixDb\Database;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?Database $db = null;
    protected ?Database $readDb = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $this->db = new Database(TEST_DSN, TEST_USERNAME, TEST_PASSWORD, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            
            $this->readDb = new Database(TEST_READ_DSN, TEST_READ_USERNAME, TEST_READ_PASSWORD, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            
            createTestTable($this->db);
            cleanupTestTable($this->db);
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            cleanupTestTable($this->db);
        }
        parent::tearDown();
    }

    protected function createTestModel(): \Tests\Fixtures\TestUserModel
    {
        return \Tests\Fixtures\TestUserModel::newInstance($this->db, $this->readDb);
    }
}
