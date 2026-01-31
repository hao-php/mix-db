<?php

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * 加载数据库配置
 * 
 * 优先级：
 * 1. database.local.php (本地配置，不提交到 Git)
 * 2. database.php (默认配置)
 */
function loadDatabaseConfig(): array
{
    $localConfigFile = __DIR__ . '/config/database.local.php';
    $defaultConfigFile = __DIR__ . '/config/database.php';
    
    if (file_exists($localConfigFile)) {
        return require $localConfigFile;
    }
    
    if (file_exists($defaultConfigFile)) {
        return require $defaultConfigFile;
    }
    
    throw new \RuntimeException(
        '数据库配置文件不存在。请创建 tests/config/database.local.php 文件。'
    );
}

// 加载配置
$dbConfig = loadDatabaseConfig();

// 定义常量（为了向后兼容）
define('TEST_DSN', $dbConfig['default']['dsn'] ?? '');
define('TEST_USERNAME', $dbConfig['default']['username'] ?? '');
define('TEST_PASSWORD', $dbConfig['default']['password'] ?? '');
define('TEST_READ_DSN', $dbConfig['read']['dsn'] ?? TEST_DSN);
define('TEST_READ_USERNAME', $dbConfig['read']['username'] ?? TEST_USERNAME);
define('TEST_READ_PASSWORD', $dbConfig['read']['password'] ?? TEST_PASSWORD);

/**
 * 创建测试表
 */
function createTestTable(\Haoa\MixDb\Database $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS test_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            age INT,
            created_at DATETIME,
            updated_at DATETIME,
            INDEX idx_user_name (user_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", []);
}

/**
 * 清理测试表
 */
function cleanupTestTable(\Haoa\MixDb\Database $db): void
{
    $db->exec("TRUNCATE TABLE test_users", []);
}

/**
 * 删除测试表
 */
function dropTestTable(\Haoa\MixDb\Database $db): void
{
    $db->exec("DROP TABLE IF EXISTS test_users", []);
}
