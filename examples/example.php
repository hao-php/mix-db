<?php

use Haoa\MixDb\Model;
use Haoa\MixDb\Database;

require __DIR__ . '/autoload.php';

class UserMode extends Model
{

    public string $table = "user";

    public function __construct()
    {
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
            //$model->setDatabase($tx);
            $model = UserMode::create($tx);

            $id = $model->insertGetId([
                'user_name' => 'test_' . rand(1, 100),
            ]);
            var_dump($model->getLastQueryLog());
            var_dump($model->getLastSql());

            $ret = $model->where('user_name', 'aa?"')->first();
            var_dump($ret, $model->getLastQueryLog());
            var_dump($model->getLastSql());

            $ret = $model->where('id', 'in', [1, 2, 3])->first();
            var_dump($ret, $model->getLastQueryLog());
            var_dump($model->getLastSql());

            $tx->rollback();
        } catch (\Throwable $e) {
            echo $e->__toString() . "\n";
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

//$ret = MyTest::select$model();
//var_dump($ret);

//$ret = MyTest::insert($model);
//var_dump($ret);

//$ret = MyTest::update($model, 2);
//var_dump($ret, $model->getLastQueryLog());

//$ret = MyTest::delete($model, 2);
//var_dump($ret->rowCount(), $model->getLastQueryLog());

MyTest::transaction($db, $model);

//$ret = UserMode::create()->first();
//var_dump($ret);