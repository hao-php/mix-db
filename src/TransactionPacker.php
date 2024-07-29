<?php

namespace Haoa\MixDb;

use Haoa\MixDatabase\Transaction;

/**
 *
 */
class TransactionPacker
{

    protected Transaction $tx;

    private int $beginNum = 0;

    public $commitEvents = [];

    public function __construct(Transaction $tx)
    {
        $this->tx = $tx;
    }

    /**
     * 提交事务
     * @throws \PDOException
     */
    public function commit()
    {
        $this->beginNum--;
        if ($this->beginNum > 0) {
            return;
        }
        $this->tx->commit();
        if (!empty($this->commitEvents)) {
            foreach ($this->commitEvents as $event) {
                $event();
            }
        }
    }

    /**
     * 回滚事务
     * @throws \PDOException
     */
    public function rollback()
    {
        $this->beginNum--;
        if ($this->beginNum > 0) {
            return;
        }
        $this->tx->rollback();
    }

    public function __call($name, $arguments = [])
    {
        return call_user_func_array([$this->tx, $name], $arguments);
    }

    public function addNum()
    {
        $this->beginNum++;
    }

    public function addCommitEvent($event)
    {
        $this->commitEvents[] = $event;
    }

}