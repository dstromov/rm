<?php

namespace rm\Services;

use rm\Exceptions\DbException;

class Db {

    private static $instance;

    /** @var  \PDO */
    private $pdo;

    //подключение к БД
    private function __construct()
    {
        $dbOptions = (require __DIR__ . '/../../settings.php')['db'];

        try {

            $this->pdo = new \PDO(
                "mysql:host={$dbOptions['host']};dbname={$dbOptions['dbname']}",
                $dbOptions['user'],
                $dbOptions['password'],  
            );
            $this->pdo->exec('SET NAMES UTF8');

        } catch (\PDOException $e) {
            throw new DbException('Ошибка подключения к БД' . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    //запрос строк или строки из БД
    public function query(string $sql, $params = [], string $className = 'stdClass'): ?array
    {                    
        $sth = $this->pdo->prepare($sql);
        $result = $sth->execute($params);
        
        if(false === $result) return null;
        return $sth->fetchAll(\PDO::FETCH_CLASS, $className);
    }

    //получение id после добавления записи в БД
    public function getLastInsertId(): int
    {
        return (int) $this->pdo->LastInsertId();
    }
}