<?php

namespace DataCollector;

class DatabaseAdapter
{

    private \mysqli $db;


    public function __construct()
    {
        $this->db = new \mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        if ($this->db->connect_errno && !$_ENV['DRY_RUN']) {
            throw new \RuntimeException('Failed connecting to database');
        }
        $this->db->set_charset('utf8mb4');
    }


    public function getDb(): \mysqli
    {
        return $this->db;
    }


    protected function insertIntoDb(string $table, array $data): bool
    {
        foreach ($data as $key => $val) {
            $data[$key] = $this->db->real_escape_string($val);
        }
        $cols = '`' . implode('`, `', array_keys($data)) . '`';
        $values = "'" . implode("', '", array_values($data)) . "'";
        return $this->db->query("INSERT INTO $table ($cols) VALUES ($values)");
    }

}