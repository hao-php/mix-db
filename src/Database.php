<?php

namespace Haoa\MixDb;

use Haoa\MixDatabase\Database as MixDb;
use Haoa\MixDatabase\Transaction;
use Haoa\Util\Context\RunContext;

class Database extends MixDb
{

    public const RunContextKey = 'obj_transaction_packer:';

    /**
     * @return TransactionPacker|Transaction
     */
    public function beginTransactionPacker(): TransactionPacker
    {
        $ctx = self::getContext();
        /** @var TransactionPacker $obj */
        $obj = $ctx->get(self::RunContextKey . $this->getObjectHash());
        if (empty($obj)) {
            $tx = parent::beginTransaction();
            $obj = new TransactionPacker($tx);
            $obj->addNum();
            $ctx->set(self::RunContextKey . $this->getObjectHash(), $obj);
        } else {
            $obj->addNum();
        }
        return $obj;
    }

    public static function getContext()
    {
        return RunContext::getHandler();
    }

    public static function queryLogToSql($log)
    {
        $sql = $log['sql'];
        if (!empty($log['bindings'])) {
            reset($log['bindings']);
            $firstKey = key($log['bindings']);
            if (is_string($firstKey)) {
                foreach ($log['bindings'] as $key => $v) {
                    $sql = str_replace(':' . $key, '"' . $v . '"', $sql);
                }
            } else {
                foreach ($log['bindings'] as $key => $v) {
                    if (is_array($v)) {
                        foreach ($v as &$vv) {
                            $vv = addslashes($vv);
                        }
                        $v = implode('","', $v);
                    } else {
                        $v = addslashes($v);
                    }
                    $log['bindings'][$key] = '"' . $v . '"';
                }
                $sql = str_replace('?', '%s', $sql);
                $sql = sprintf($sql, ...$log['bindings']);
            }
        }
        return $sql;
    }

    public function getObjectHash()
    {
        return spl_object_hash($this);
    }

}