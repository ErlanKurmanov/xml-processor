<?php

class Database {
    private PDO $pdo;

    public function __construct() {
        $dsn = "pgsql:host=db;port=5432;dbname=family_db;";
        $this->pdo = new PDO($dsn, 'user', 'password', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }
}