<?php

namespace FpDbTest;

use Exception;
use mysqli;
use BuildSql\builderSql;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $builder = new builderSql();
        return $builder->start($query, $args);
    }

    public function skip(): string
    {
        return '@null';
    }
}
