<?php


namespace App\Redis;


abstract class AbstractCommand
{
    protected $key;
    protected $connection;

    public function __construct(string $key, \Redis $connection)
    {
        $this->key = $key;
        $this->connection = $connection;
    }

    public function del():int
    {
        return $this->connection->del($this->key);
    }
}