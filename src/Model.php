<?php

namespace Haoa\MixDb;

use Mix\Database\ConnectionInterface;
use Mix\Database\Database;
use Haoa\MixDb\Database as HaoDatabase;

/**
 *
 */
abstract class Model
{

    const WRITE = 1;

    const READ = 2;

    public string $table;

    /**
     * 主库, 必须设置
     * @var Database|TransactionPacker
     */
    protected Database|TransactionPacker $database;

    protected Database|TransactionPacker|null $writeDatabase = null;

    protected Database|TransactionPacker|null $readDatabase = null;

    protected $lastDbName = '';

    /**
     * 是否使用事务对象
     * @var bool
     */
    protected bool $useTran = true;

    /**
     * 更新的时候自动写入修改时间
     * @var string
     */
    protected string $updateTimeField = 'updated_at';

    /**
     * 创建的时候自动写入创建时间
     * @var string
     */
    protected string $createTimeField = 'created_at';


    protected string $alias = '';

    protected array $wheres = [];

    protected array $ors = [];

    protected int $offset = 0;

    protected int $limit = 0;

    protected string $fields = '';

    protected array $havings = [];

    protected array $orders = [];

    protected array $group = [];

    protected array $joins = [];

    protected array $leftJoins = [];

    protected ?\Closure $debug;

    protected array $lastQueryLog = [];

    public function __construct()
    {
    }

    protected function buildUpdateTime($time = null)
    {
        // 创建的时候, 修改时间使用创建时间
        if (!empty($time)) {
            return $time;
        }
        return time();
    }

    protected function buildCreateTime()
    {
        return time();
    }

    protected function reset()
    {
        $this->alias = '';
        $this->wheres = [];
        $this->ors = [];
        $this->offset = 0;
        $this->limit = 0;
        $this->fields = '';
        $this->havings = [];
        $this->orders = [];
        $this->group = [];
        $this->joins = [];
        $this->leftJoins = [];
        $this->debug = null;
    }

    protected function buildWhere(...$where)
    {
        $countWhere = count($where);
        if ($countWhere == 1) {
            if (isset($where[0][0]) && is_array($where[0][0])) {
                $where = $where[0];
            }
        } elseif ($countWhere == 2) {
            $where = [$where];
        } elseif ($countWhere == 3) {
            $where = [$where];
        }
        $stringArr = [];
        $values = [];
        foreach ($where as $w) {
            $wCount = count($w);
            if ($wCount != 2 && $wCount != 3) {
                throw new \Exception("where格式错误");
            }

            if ($wCount == 2) {
                $field = $w[0];
                $option = '=';
                $value = $w[1];
            } else {
                $field = $w[0];
                $option = $w[1];
                $value = $w[2];
            }

            if (is_array($value)) {
                $stringArr[] = $field . ' ' . strtoupper($option) . ' (?)';
            } else {
                $stringArr[] = $field . ' ' . strtoupper($option) . ' ?';
            }

            $values[] = $value;
        }
        return ['(' . implode(' AND ', $stringArr) . ')', $values];
    }

    protected function buildQuery(ConnectionInterface &$conn, $options = [])
    {
        if (!empty($this->wheres)) {
            foreach ($this->wheres as $where) {
                $conn->where($where[0], ...$where[1]);
            }
        }

        if (!empty($this->ors)) {
            foreach ($this->ors as $or) {
                $conn->or($or[0], ...$or[1]);
            }
        }

        if (!empty($this->offset)) {
            $conn->offset($this->offset);
        }

        if (!empty($this->limit)) {
            $conn->limit($this->limit);
        }

        if (!empty($this->fields)) {
            $conn->select($this->fields);
        }

        if (!empty($this->havings)) {
            foreach ($this->havings as $having) {
                $conn->having($having[0], ...$having[1]);
            }
        }

        if (!empty($this->orders)) {
            foreach ($this->orders as $order) {
                $conn->order($order[0], $order[1]);
            }
        }

        if (!empty($this->group)) {
            $conn->group(...$this->group);
        }

        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $conn->join($join[0], $join[1], ...$join[2]);
            }
        }

        if (!empty($this->leftJoins)) {
            foreach ($this->leftJoins as $join) {
                $conn->leftJoin($join[0], $join[1], ...$join[2]);
            }
        }

        if (!empty($this->debug)) {
            $conn->debug($this->debug);
        }

        $this->reset();
    }

    public function getConn(int $connType = 0): ConnectionInterface
    {
        if (empty($this->table)) {
            throw new \Exception("table is empty");
        }
        $table = $this->table;
        if (!empty($this->alias)) {
            $table .= " AS " . $this->alias;
        }
        $db = null;
        if ($this->useTran) {
            $db = HaoDatabase::getContext()->get(HaoDatabase::RunContextKey);
            if (!empty($db)) {
                $this->lastDbName = 'tran';
            }
        }
        if (empty($db)) {
            switch ($connType) {
                case self::WRITE:
                    $db = $this->writeDatabase;
                    if (!empty($db)) {
                        $this->lastDbName = 'write';
                    }
                    break;
                case self::READ:
                    $db = $this->readDatabase;
                    if (!empty($db)) {
                        $this->lastDbName = 'read';
                    }
                    break;
            }
        }
        if (empty($db)) {
            $db = $this->database;
            $this->lastDbName = 'default';
        }
        return $db->table($table);
    }

    public function setDatabase(Database|TransactionPacker $db)
    {
        $this->database = $db;
    }

    public function setWriteDatabase(Database|TransactionPacker $db)
    {
        $this->writeDatabase = $db;
    }

    public function setReadDatabase(Database|TransactionPacker $db)
    {
        $this->readDatabase = $db;
    }

    public static function create(Database|TransactionPacker|null $db = null, $useTran = true): static
    {
        $obj = new static();
        $obj->useTran = $useTran;
        if (!empty($db)) {
            $obj->setDatabase($db);
            $obj->setWriteDatabase($db);
            $obj->setReadDatabase($db);
        }
        return $obj;
    }

    public function notTran()
    {
        $this->useTran = false;
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function alias(string $alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @param mixed $where
     * e.g.二维数组
     * [
     *    [$field1, '=', $value1],
     *    [$field2, '>', $value2],
     *    [$field3, $value3]
     * ]
     * e.g. where($field, $value)
     * e.g. where($field, $option, $value)
     * @return $this
     */
    public function where(...$where)
    {
        if (empty($where) || empty($where[0])) {
            return $this;
        }
        list($string, $values) = $this->buildWhere(...$where);
        $this->wheres[] = [$string, $values];
        return $this;
    }

    public function whereString(string $whereString, ...$values)
    {
        $this->wheres[] = [$whereString, $values];
        return $this;
    }

    public function whereOr(...$where)
    {
        if (empty($where)) {
            return $this;
        }
        list($string, $values) = $this->buildWhere(...$where);
        $this->ors[] = [$string, $values];
        return $this;
    }

    public function offset(int $offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function page(int $page, int $limit)
    {
        $this->offset = ($page - 1) * $limit;
        $this->limit = $limit;
        return $this;
    }

    public function buildField(string $fields)
    {
        $fields = str_replace('`', '', $fields);
        $fields = explode(',', $fields);
        foreach ($fields as &$field) {
            $fieldArr = explode('.', $field);
            foreach ($fieldArr as &$v) {
                $v = '`' . trim($v, '` ') . '`';
            }
            $field = implode('.', $fieldArr);
        }
        $fields = implode(',', $fields);

        return $fields;
    }

    public function select(string $fields)
    {
        // $fields = $this->buildField($fields);
        $this->fields = $fields;
        return $this;
    }

    public function having(...$where)
    {
        if (empty($where)) {
            return $this;
        }
        list($string, $values) = $this->buildWhere(...$where);
        $this->havings[] = [$string, $values];
        return $this;
    }

    public function havingString(string $expr, ...$values)
    {
        $this->havings[] = [$expr, $values];
        return $this;
    }

    public function group(string ...$fields)
    {
        $this->group = $fields;
        return $this;
    }

    public function order(string $field, string $order)
    {
        $this->orders[] = [$field, $order];
        return $this;
    }

    public function join(string $table, string $on, ...$values)
    {
        $this->joins[] = [$table, $on, $values];
        return $this;
    }

    public function leftJoin(string $table, string $on, ...$values)
    {
        $this->leftJoins[] = [$table, $on, $values];
        return $this;
    }


    /**
     * $return['time'] 执行时间, 毫秒
     * $return['sql'] sql
     * $return['bindings'] 绑定的数据
     * @return array
     */
    public function getLastQueryLog(): array
    {
        return $this->lastQueryLog;
    }

    public function getLastSql(): string
    {
        $log = $this->getLastQueryLog();
        if (empty($log)) {
            return '';
        }
        return HaoDatabase::queryLogToSql($log);
    }

    public function getLastDbName()
    {
        return $this->lastDbName;
    }


    /**
     * @return int 受影响行数
     */
    public function update(string $field, $value)
    {
        $data = [
            $field => $value
        ];
        return $this->updates($data);
    }

    /**
     * @return int 受影响行数
     */
    public function updates(array $data)
    {
        if (empty($this->wheres)) {
            throw new \Exception('update操作必须带条件');
        }
        if ($this->updateTimeField && !isset($data[$this->updateTimeField])) {
            $data[$this->updateTimeField] = $this->buildUpdateTime();
        }
        $conn = $this->getConn(self::WRITE);
        $this->buildQuery($conn);
        $ret = $conn->updates($data)->rowCount();
        $this->lastQueryLog = $conn->queryLog();
        return $ret;
    }

    /**
     *
     */
    public function insert(array $data, $insert = 'INSERT INTO'): ConnectionInterface
    {
        $createTime = null;
        if ($this->createTimeField && !isset($data[$this->createTimeField])) {
            $createTime = $data[$this->createTimeField] = $this->buildCreateTime();
        }

        if ($this->updateTimeField && !isset($data[$this->updateTimeField])) {
            $data[$this->updateTimeField] = $this->buildUpdateTime($createTime);
        }
        $conn = $this->getConn(self::WRITE);
        $this->buildQuery($conn);
        $ret = $conn->insert($this->table, $data, $insert);
        $this->lastQueryLog = $conn->queryLog();
        return $ret;
    }

    /**
     *
     */
    public function insertGetId(array $data, $insert = 'INSERT INTO'): string
    {
        $createTime = null;
        if ($this->createTimeField && !isset($data[$this->createTimeField])) {
            $createTime = $data[$this->createTimeField] = $this->buildCreateTime();
        }

        if ($this->updateTimeField && !isset($data[$this->updateTimeField])) {
            $data[$this->updateTimeField] = $this->buildUpdateTime($createTime);
        }
        $conn = $this->getConn(self::WRITE);
        $this->buildQuery($conn);
        $ret = $conn->insert($this->table, $data, $insert);
        $id = $ret->lastInsertId();
        $this->lastQueryLog = $conn->queryLog();
        return $id;
    }

    /**
     * @return int 受影响行数
     */
    public function batchInsert(array $list, $insert = 'INSERT INTO')
    {
        foreach ($list as &$data) {
            $createTime = $this->buildCreateTime();
            if ($this->createTimeField && !isset($data[$this->createTimeField])) {
                $data[$this->createTimeField] = $createTime;
            }

            if ($this->updateTimeField && !isset($data[$this->updateTimeField])) {
                $data[$this->updateTimeField] = $this->buildUpdateTime($createTime);
            }
        }
        $conn = $this->getConn(self::WRITE);
        $this->buildQuery($conn);
        $ret = $conn->batchInsert($this->table, $list, $insert)->rawCount();
        $this->lastQueryLog = $conn->queryLog();
        return $ret;
    }

    public function delete()
    {
        if (empty($this->wheres)) {
            throw new \Exception('delete操作必须带条件');
        }
        $conn = $this->getConn(self::WRITE);
        $this->buildQuery($conn);
        $ret = $conn->delete();
        $this->lastQueryLog = $conn->queryLog();
        return $ret;
    }

    public function debug(\Closure $debug)
    {
        $this->debug = $debug;
        return $this;
    }

    public function count()
    {
        $conn = $this->getConn(self::READ);
        unset($this->fields);
        $this->buildQuery($conn);
        $ret = $conn->select('count(*) as mix_count')->first();
        $this->lastQueryLog = $conn->queryLog();
        return (int)($ret['mix_count'] ?? 0);
    }

    public function value(string $field)
    {
        $conn = $this->getConn(self::READ);
        unset($this->fields);
        $this->fields = $field;
        $this->buildQuery($conn);
        $result = $conn->first();
        $this->lastQueryLog = $conn->queryLog();
        $isArray = is_array($result);
        if ($isArray) {
            return $result[$field] ?? null;
        }
        return $result->$field ?? null;
    }

    public function get()
    {
        $conn = $this->getConn(self::READ);
        $this->buildQuery($conn);
        $ret = $conn->get();
        $this->lastQueryLog = $conn->queryLog();
        return $ret;
    }

    public function first()
    {
        $conn = $this->getConn(self::READ);
        $this->buildQuery($conn);
        $ret = $conn->first();
        $this->lastQueryLog = $conn->queryLog();
        return $ret;
    }

    public function statement()
    {
        $conn = $this->getConn(self::READ);
        $this->buildQuery($conn);
        $ret = $conn->statement();
        $this->lastQueryLog = $conn->queryLog();
        return $ret;
    }


    // public function __call($name, $arguments = [])
    // {
    //     $conn = $this->getConn();
    //     $this->buildQuery($conn);
    //     $ret = call_user_func_array([$conn, $name], $arguments);
    //     $this->lastQueryLog = $conn->queryLog();
    //     return $ret;
    // }


}