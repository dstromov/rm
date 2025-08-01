<?php

namespace rm\Models;

use rm\Services\Db; //TODO проверить что есть подключение к БД


abstract class ActiveRecordEntity
{

    /** @var int */
    protected $id;

    /** //геттер айдишника объекта класса
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function __set($name, $value)
    {
        $camelCaseName = $this->underscoreToCamelCase($name);
        $this->$camelCaseName = $value;
    }

    //абстракный метод, который нужно реализовать в каждом дочернем классе
    abstract protected static function getTableName(): string;

    //статические функции, обеспечивающие разные запросы к БД
    /**
     * @return static[]
     */
    public static function findAll(): array
    {
        $db = Db::getInstance();
        return $db->query('SELECT * FROM `' .  static::getTableName() . '`;',[], static::class);
    }

    public static function getById(int $id): ?self
    {
        $db = Db::getInstance();
        $entities = $db->query(
            'SELECT * FROM `' . static::getTableName() . '` WHERE id=:id;',
            [':id' => $id],
            static::class,
        );

        return $entities ? $entities[0] : null;
    }

    public static function findOneByColumn(string $columnName, $value): ?self
    {
        $db = Db::getInstance();
        $entities = $db->query(
            'SELECT * FROM `' . static::getTableName() . '` WHERE `' . $columnName . '` = :value LIMIT 1;',
            [':value' => $value],
            static::class,
        );

        return $entities ? $entities[0] : null;
    }

    public static function findIsNullByColumn(string $columnName): ?self
    {
        $db = Db::getInstance();
        $entities = $db->query(
            'SELECT * FROM `' . static::getTableName() . '` WHERE `' . $columnName . '` IS NULL LIMIT 1',
            [],
            static::class,
        );

        return $entities ? $entities[0] : null;
    }

    //ниже два публичных метода редактирования данных БД - добавление, редактирование, удаление
    public function save()
    {
        $mappedProperties = $this->mapPropertiesToDbFormat();

        if ($this->id !== null) {
            $this->update($mappedProperties);
        } else {
            $this->insert($mappedProperties);
        }
    }

    public function delete()
    {
        $sql = 'DELETE FROM `' . static::getTableName() . '` WHERE id = :id';
        
        $params = [':id' => $this->id];
        
        $db = Db::getInstance();
        $db->query($sql, $params, static::class);

        //ввиду удаления сущностьи у объекта ставим id = null
        $this->id = null;
    }

    //------------------------------------------------------------------
    //приватные методы, обеспечивающие подкапотную работу при изменении данных в БД (C,U,D из CRUD)
    private function update(array $mappedProperties): void
    {
        $colums2params = [];
        $params2values = [];
        $index = 1;
        foreach ($mappedProperties as $column => $value) {
            $param = ":param{$index}"; //:param1;
            $colums2params[] = "{$column} = {$param}";
            $params2values[$param] = $value;
            $index++;
        }

        $sql = 'UPDATE ' . static::getTableName() . ' SET ' . implode(', ', $colums2params) . ' WHERE id = ' . $this->id;
        $db = Db::getInstance();
        $db->query($sql, $params2values, static::class);
    }
    
    private function insert(array $mappedProperties): void
    {
        $colums2params = [];
        $params2values = [];
        $index = 1;
        foreach ($mappedProperties as $column => $value) {
            if ($value !== null) {
                $param = ":param{$index}"; //:param1;
                $colums2params[] = "{$column} = {$param}";
                $params2values[$param] = $value;
                $index++;
            }  
        }

        $sql = 'INSERT ' . static::getTableName() . ' SET ' . implode(', ', $colums2params);

        $db = Db::getInstance();
        $db->query($sql, $params2values, static::class);
        $this->id = $db->getLastInsertId();

        $objectForm = static::getById($this->getId());

        foreach ($objectForm as $property => $value) {
            $this->$property = $value;
        }
    }

    private function underscoreToCamelCase(string $sourse): string 
    {
        // $changeToUpper = ucwords($sourse,'_');
        // return lcfirst(str_replace('_', '', $changeToUpper));
        
        return $sourse;
    }

    private function camelCaseToUnderscore(string $sourse): string
    {
        // $addUnderscore = preg_replace('/(?<!^)[A-Z]/', '_$0', $source);
        // return strtolower($addUnderscore);

        return $sourse;
    }

    private function mapPropertiesToDbFormat(): array
    {
        $reflector = new \ReflectionObject($this);
        $properties = $reflector->getProperties();

        $mappedProperties = [];
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $propertyNameLikeBd = $this->camelCaseToUnderscore($propertyName);
            $mappedProperties[$propertyNameLikeBd] = $this->$propertyName;
        }

        return $mappedProperties;

        
    }
}




