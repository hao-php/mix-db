<?php

use Haoa\MixDb\Database;
use Haoa\MixDb\Model;

require __DIR__ . '/autoload.php';

class UserMode extends Model
{

    public static string $tableName = "user";

    public function __construct()
    {
        parent::__construct();
    }

    protected function buildUpdateTime($time = null)
    {
        // 创建的时候, 修改时间使用创建时间
        if (!empty($time)) {
            return $time;
        }
        return date('Y-m-d H:i:s');
    }

    protected function buildCreateTime()
    {
        return date('Y-m-d H:i:s');
    }

}

class MyTest
{

    public static function first(UserMode $model)
    {
        return $model->first();
    }

    public static function insert(UserMode $model)
    {
        return $model->insertGetId([
            'user_name' => 'test_' . rand(1, 100),
        ]);
    }

    public static function update(UserMode $model, $id)
    {
        return $model->where('id', $id)->update('user_name', 'test_' . rand(1, 100));
    }

    public static function delete(UserMode $model, $id)
    {
        return $model->where('id', $id)->delete();
    }

    public static function count(UserMode $model)
    {
        return $model->count();
    }

    public static function column(UserMode $model)
    {
        return $model->column('user_name');
    }

    public static function read(UserMode $model, $id)
    {
        $ret = MyTest::first($model);
        self::println('first', $ret, $model->getLastSql(), $model->getLastConnectionType());

        $ret = $model->get();
        self::println('get', $ret, $model->getLastSql(), $model->getLastConnectionType());

        $ret = $model->value('user_name');
        self::println('value', $ret, $model->getLastSql(), $model->getLastConnectionType());

        $ret = MyTest::count($model);
        self::println('count', $ret, $model->getLastSql(), $model->getLastConnectionType());

        $ret = MyTest::column($model);
        self::println('column', $ret, $model->getLastSql(), $model->getLastConnectionType());
    }

    public static function println($method, $ret, $sql, $connectionType)
    {
        $arr = [
            'method' => $method,
            'ret' => $ret,
            'sql' => $sql,
            'connectionType' => $connectionType,
        ];
        var_dump($arr);
    }

    public static function transaction(Database $db, Database $readDb, UserMode $model)
    {
        $tx = $db->beginTransaction();
        try {
            echo "\n=====================default db=========================\n";
            $model = UserMode::newInstance($db);

            $id = $model->insertGetId([
                'user_name' => 'test_' . rand(1, 100),
            ]);
            // var_dump($model->getLastQueryLog());
            var_dump($model->getLastSql());
            var_dump($model->getLastConnectionType());

            $ret = $model->where('user_name', 'aa?"')->first();
            // var_dump($ret, $model->getLastQueryLog());
            var_dump($model->getLastSql());
            var_dump($model->getLastConnectionType());

            $ret = $model->where('id', 'in', [1, 2, 3])->first();
            // var_dump($ret, $model->getLastQueryLog());
            var_dump($model->getLastSql());
            var_dump($model->getLastConnectionType());
            echo "=====================default db=========================\n";

            echo "=====================read db=========================\n";
            $model2 = UserMode::newInstance($readDb);
            $model2->first();
            var_dump($model2->getLastSql());
            var_dump($model2->getLastConnectionType());
            $model2->insertGetId([
                'user_name' => 'test2_' . rand(1, 100),
            ]);
            var_dump($model2->getLastSql());
            var_dump($model2->getLastConnectionType());
            echo "=====================read db=========================\n";

            $tx->rollback();
        } catch (\Throwable $e) {
            echo $e->__toString() . "\n";
            $tx->rollback();
        }
    }

    public static function transaction2(Database $db, Database $readDb, UserMode $model)
    {
        $tx = $db->beginTransaction();
        $tx->addCommitCallback(function () {
            var_dump("CommitCallback");
        });
        try {
            self::transaction($db, $readDb, $model);
            $tx->commit();
//            $tx->rollback();
        } catch (\Throwable $e) {
            $tx->rollback();
        }

    }


}

$db = new Database('mysql:host=mysql8;port=3306;charset=utf8mb4;dbname=my_test', 'root', 'dcqhmsql', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::ATTR_TIMEOUT => 5,
]);
$db->startPool(100, 1);

$readDb = new Database('mysql:host=mysql8;port=3306;charset=utf8mb4;dbname=my_test', 'root', 'dcqhmsql', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
//    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::ATTR_TIMEOUT => 5,
]);
$readDb->startPool(100, 1);
$model = UserMode::newInstance($db, $readDb);

//$id = MyTest::insert($model);
//MyTest::println('insert', $id, $model->getLastSql(), $model->getLastConnectionType());
//MyTest::read($model, $id);
//echo "=================================================================\n";
//
//$ret = MyTest::update($model, $id);
//MyTest::println('update', $id, $model->getLastSql(), $model->getLastConnectionType());
//MyTest::read($model, $id);
//echo "=================================================================\n";
//
// $ret = MyTest::delete($model, $id);
//MyTest::println('delete', $id, $model->getLastSql(), $model->getLastConnectionType());
//MyTest::read($model, $id);
//echo "=================================================================\n";

//$ret = $model->whereString("1=1")->delete();
//MyTest::println('deleteAll', $ret, $model->getLastSql(), $model->getLastConnectionType());
//MyTest::read($model, 0);

// $ret = $db->raw("select * from user where id=?", [1])->first();
// var_dump($ret->queryLog());

MyTest::transaction2($db, $readDb, $model);

// $tx = $db->beginTransaction();
// try {
//     $tx->insert('user', [
//         'user_name' => 'test1_' . rand(1, 100),
//     ]);
//
//     $db->insert('user', [
//         'user_name' => 'test2_' . rand(1, 100),
//     ]);
//
//     $db->insert('user', [
//         'user_name' => 'test3_' . rand(1, 100),
//     ], false);
//     $tx->rollback();
// } catch (\Throwable $e) {
//     $tx->rollback();
// }