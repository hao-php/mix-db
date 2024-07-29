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

    public static function select(UserMode $model)
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

    public static function transaction(Database $db, UserMode $model)
    {
        $tx = $db->beginTransactionPacker();
        try {
            $model = UserMode::create($db);

            $id = $model->insertGetId([
                'user_name' => 'test_' . rand(1, 100),
            ]);
            // var_dump($model->getLastQueryLog());
            var_dump($model->getLastSql());
            var_dump($model->getLastDbName());

            $ret = $model->where('user_name', 'aa?"')->first();
            // var_dump($ret, $model->getLastQueryLog());
            var_dump($model->getLastSql());
            var_dump($model->getLastDbName());

            $ret = $model->where('id', 'in', [1, 2, 3])->first();
            // var_dump($ret, $model->getLastQueryLog());
            var_dump($model->getLastSql());
            var_dump($model->getLastDbName());

            $model2 = UserMode::create($db, false);
            $model2->first();
            var_dump($model2->getLastSql());
            var_dump($model2->getLastDbName());
            $model2->insertGetId([
                'user_name' => 'test2_' . rand(1, 100),
            ]);
            var_dump($model2->getLastSql());
            var_dump($model2->getLastDbName());

            $db->insert('user', [
                'user_name' => 'test3_' . rand(1, 100),
            ]);

            $tx->rollback();
        } catch (\Throwable $e) {
            echo $e->__toString() . "\n";
            $tx->rollback();
        }
    }

    public static function transaction2(Database $db, UserMode $model)
    {
        $tx = $db->beginTransactionPacker();
        $tx->addCommitEvent(function () {
            var_dump("CommitEvent");
        });
        try {
            self::transaction($db, $model);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollback();
        }

    }


}

$db = new Database('mysql:host=mysql8;port=3306;charset=utf8mb4;dbname=my_test', 'test', '123456', [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::ATTR_TIMEOUT => 5,
]);
$model = new UserMode();
$model->setDatabase($db);
// $model->setReadDatabase($db);
// $model->setWriteDatabase($db);

// $ret = MyTest::select($model);
// var_dump($ret, $model->getLastDbName());

// $ret = MyTest::insert($model);
// var_dump($ret, $model->getLastDbName());

// $ret = MyTest::update($model, 2);
// var_dump($ret, $model->getLastSql(), $model->getLastDbName());

// $ret = MyTest::delete($model, 2);
// var_dump($ret->rowCount(), $model->getLastSql(), $model->getLastDbName());

MyTest::transaction2($db, $model);

//$ret = UserMode::create()->first();
//var_dump($ret);