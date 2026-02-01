# Mix-DB 测试说明

本文档说明如何在 `mix-db` 项目中运行 PHPUnit 测试用例。

## 前置要求

### 1. PHP 环境
- **PHP 版本**: PHP 8.0+（推荐 PHP 8.1+）
- **必需扩展**: `ext-pdo` 和 `ext-pdo_mysql`

### 2. 数据库
- **MySQL**: 需要 MySQL 5.7+ 或 MariaDB 10.2+
- **数据库**: 需要创建一个测试数据库

## 安装步骤

### 1. 安装项目依赖

在项目根目录（`mix-db/`）运行：

```bash
composer install
```

### 2. 配置数据库连接

创建数据库配置文件：

```bash
cd tests/config
cp database.php database.local.php
```

编辑 `tests/config/database.local.php`，修改数据库连接信息：

```php
return [
    // 主数据库连接（用于写操作）
    'default' => [
        'dsn' => 'mysql:host=localhost;port=3306;charset=utf8mb4;dbname=test_mix_db',
        'username' => 'your_username',
        'password' => 'your_password',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT => 5,
        ],
    ],

    // 读数据库连接（用于读写分离，可选）
    'read' => [
        'dsn' => 'mysql:host=localhost;port=3306;charset=utf8mb4;dbname=test_mix_db',
        'username' => 'your_username',
        'password' => 'your_password',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT => 5,
        ],
    ],
];
```

**注意**: `database.local.php` 文件不会被提交到 Git，用于本地开发配置。

### 3. 创建测试数据库

在 MySQL 中创建测试数据库：

```sql
CREATE DATABASE test_mix_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 运行测试

### 运行所有测试

```bash
# 使用 Composer 脚本
composer test

# 或者直接使用 phpunit
vendor/bin/phpunit
```

### 运行特定测试文件

```bash
# 只运行 DatabaseTest
vendor/bin/phpunit tests/DatabaseTest.php

# 只运行 ModelTest
vendor/bin/phpunit tests/ModelTest.php
```

### 运行特定测试方法

```bash
# 运行特定测试方法
vendor/bin/phpunit --filter testConstruct
vendor/bin/phpunit --filter testTransaction
```

### 生成测试覆盖率报告

```bash
# 生成 HTML 覆盖率报告
composer test-coverage

# 或者直接使用 phpunit
vendor/bin/phpunit --coverage-html coverage
```

覆盖率报告将生成在 `coverage/` 目录下，用浏览器打开 `coverage/index.html` 查看。

## 测试配置

### PHPUnit 配置文件

项目使用 `phpunit.xml` 配置文件：

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.0/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         failOnWarning="true"
         failOnRisky="true"
         beStrictAboutOutputDuringTests="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```
