<?php

namespace Tests\Fixtures;

use Haoa\MixDb\Model;

class TestUserModel extends Model
{
    public static string $tableName = "test_users";

    protected function buildUpdateTime($time = null)
    {
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
