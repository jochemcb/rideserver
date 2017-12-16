<?php
namespace DB;

class Database
{
    const DB_HOST = 'localhost';
    const DB_NAME = 'ride';
    const DB_USER = 'root';
    const DB_PASSWORD = 'root';
    private $pdo = null;
    private $inst = null;

    /**
     * Open the database connection
     */
    public function __construct()
    {
        // open database connection
        $conStr = "pgsql:host=localhost;dbname=ride;user=ride_user;password=justcheck";
        try {
            $this->pdo = new \PDO($conStr);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $error = $e->getMessage();
            throw new \Exception($e);
        }
    }

    public function __destruct()
    {
        // close the database connection
        $this->pdo = null;
    }
    public static function Instance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new Database();
        }
        return $inst;
    }
    public function exec($sql)
    {
        try {
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            $error = $e->getMessage();
            throw new \Exception($e);
        }
    }
    private function prepare($sql)
    {
        try {
            $this->pdo->prepare($sql);
        } catch (\PDOException $e) {
            $error = $e->getMessage();
            throw new \Exception($e);
        }
    }

    public function insert($sql, $values)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($values as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $lastid = $this->pdo->lastInsertId();
            return $lastid;
        } catch (\PDOException $e) {
            $error = $e->getMessage();
            throw new \Exception($e);
        }
    }
    public function update($sql, $values)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($values as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $count = $stmt->execute();
            return $count;
        } catch (\PDOException $e) {
            $error = $e->getMessage();
            throw new \Exception($e);
        }
    }
    public function delete($sql, $values)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($values as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $count = $stmt->execute();
            return $count;
        } catch (\PDOException $e) {
            $error = $e->getMessage();
            throw new \Exception($e);
        }
    }
    public function select($sql, $values)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            //        $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            foreach ($values as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $array = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $array;
        } catch (\PDOException $e) {
            $error = $e->getMessage();
            throw new \Exception($e);
        }
    }
}
