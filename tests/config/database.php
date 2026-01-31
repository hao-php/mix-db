<?php

/**
 * 测试数据库配置文件
 * 
 * 请复制此文件为 database.local.php 并修改配置
 */

return [
    // 主数据库连接（用于写操作）
    'default' => [
        'dsn' => 'mysql:host=localhost;port=3306;charset=utf8mb4;dbname=test_mix_db',
        'username' => 'root',
        'password' => 'password',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT => 5,
        ],
    ],

    // 读数据库连接（用于读写分离，可选）
    'read' => [
        'dsn' => 'mysql:host=localhost;port=3306;charset=utf8mb4;dbname=test_mix_db',
        'username' => 'root',
        'password' => 'password',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT => 5,
        ],
    ],
];
