<?php

namespace Haoa\MixDb;

use Mix\Database\Transaction;

/**
 *
 */
class TransactionPacker
{

    protected Transaction $tx;

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
        $this->tx->commit();
    }

    /**
     * 回滚事务
     * @throws \PDOException
     */
    public function rollback()
    {
        $this->tx->rollback();
    }

    public function __call($name, $arguments = [])
    {
        return call_user_func_array([$this->tx, $name], $arguments);
    }

}