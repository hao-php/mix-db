<?php

namespace Haoa\MixDb;

use Haoa\MixDatabase\Transaction;

/**
 *
 */
class TransactionWrapper
{

    protected Transaction $tx;

    protected Database $db;

    /**
     * @var int 嵌套等级
     */
    private int $nestingLevel = 0;

    public array $commitCallbacks = [];

    public function __construct(Transaction $tx, Database $db)
    {
        $this->tx = $tx;
        $this->db = $db;
    }

    /**
     * 提交事务
     * @throws \PDOException
     */
    public function commit()
    {
        $this->nestingLevel--;
        if ($this->nestingLevel > 0) {
            return;
        }
        $this->tx->commit();
        $this->db->delContextTx();
        if (!empty($this->commitCallbacks)) {
            foreach ($this->commitCallbacks as $callback) {
                $callback();
            }
        }
    }

    /**
     * 回滚事务
     * @throws \PDOException
     */
    public function rollback()
    {
        $this->nestingLevel--;
        if ($this->nestingLevel > 0) {
            return;
        }
        $this->tx->rollback();
        $this->db->delContextTx();
    }

    public function __call($name, $arguments = [])
    {
        return call_user_func_array([$this->tx, $name], $arguments);
    }

    public function incrementNestingLevel()
    {
        $this->nestingLevel++;
    }

    /**
     * 添加事务提交后执行的回调函数, 将在commit后执行
     * @param callable $callback
     * @return void
     */
    public function addCommitCallback(callable $callback)
    {
        $this->commitCallbacks[] = $callback;
    }

}