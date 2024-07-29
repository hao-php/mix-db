<?php

namespace Haoa\MixDb;

use Haoa\MixDatabase\Transaction;

/**
 *
 */
class TransactionPacker
{

    protected Transaction $tx;

    protected Database $db;

    private int $beginNum = 0;

    public $commitEvents = [];

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
        $this->beginNum--;
        if ($this->beginNum > 0) {
            return;
        }
        $this->tx->commit();
        $this->db->delContextTx();
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
        $this->db->delContextTx();
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