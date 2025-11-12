<?php
/**
 * MySQL 데이터베이스 연결 관리자
 */
class Db
{
    private static $instance = null;
    private $pdo;
    private $driver = 'mysql';

    private function __construct()
    {
        try {
            // MySQL 연결 설정
            $host = Env::get('DB_HOST', 'localhost');
            $port = Env::get('DB_PORT', '3306');
            $dbname = Env::get('DB_NAME', 'mycomp_db');
            $username = Env::get('DB_USER', 'mycomp_user');
            $password = Env::get('DB_PASS', 'mycomp_pass123');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_TIMEOUT => 5
            ]);

        } catch (PDOException $e) {
            error_log("MySQL 연결 실패: " . $e->getMessage());
            throw new Exception("MySQL 데이터베이스 연결에 실패했습니다. MySQL 서버가 실행 중인지 확인하세요.");
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection()
    {
        return $this->pdo;
    }
    
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("쿼리 실행 실패: " . $e->getMessage() . " SQL: " . $sql);
        }
    }
    
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    public function commit()
    {
        return $this->pdo->commit();
    }
    
    public function rollback()
    {
        return $this->pdo->rollback();
    }
    
    public function getDriver()
    {
        return $this->driver;
    }
}