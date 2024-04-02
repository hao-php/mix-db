<?php

namespace Haoa\MixDb;

use Mix\Database\Database as MixDb;
use Mix\Database\Transaction;

class Database extends MixDb
{

    /**
     * @return TransactionPacker|Transaction
     */
    public function beginTransactionPacker(): TransactionPacker
    {
        $tx = parent::beginTransaction();
        return new TransactionPacker($tx);
    }

}