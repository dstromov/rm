<?php

namespace rm\Models\BuildResources;

use rm\Models\ActiveRecordEntity;
use rm\Services\Db;

class BuildResource extends ActiveRecordEntity
{
    protected $name; //string
    protected $param; //string
    protected $paramName; //string
    protected $unit; //string
    
    protected $resourceType; //string
    // Ключи в БД видов работ:
    // worker - 'Затраты труда рабочих-строителей и машинистов'
    // machine - 'Машины и механизмы'
    // equipment - 'Оборудование'
    // material - 'Материалы'
    // mainWork - 'Основные работы'

    protected $resourceCode; //string   
    protected $cost; //float //стоимость за еденицу ресурса в рублях
    protected $unc_sort; //int

    protected $cts; //string

    //создал приватный конструктор, чтобы напрямую через new никто не создал объект
    private function _construct()
    {
    
    }
    
    //необходимые сеттеры на каждое поле
    private function setResourceType(string $resourceType)
    {
        $this->resourceType = $resourceType;
    }

    private function setResourceCode(string $resourceCode)
    {
        $this->resourceCode = $resourceCode;
    }
    
    private function setName(string $name)
    {
        $this->name = $name;
    }

    private function setUnit(string $unit)
    {
        $this->unit = $unit;
    }

    private function setCost(float $cost)
    {
        $this->cost = $cost;
    }

    //таблица БД, которая совпадает с этим классом - наличие этой функции требует родительский класс
    protected static function getTableName():string
    {
        return 'uncTechnicalSpecification';
    }


    public static function findBuildResource(array $resourceData): ?self
    {
        $db = Db::getInstance();
        $sql = 'SELECT * FROM `' . static::getTableName() . '` WHERE 
        `resourceType` = :resourceType 
        AND `resourceCode` = :resourceCode 
        AND `name` = :name 
        AND `unit` = :unit 
        AND ABS(`cost` - :cost) < `cost` * 0.005
        order by 
        abs(`cost` - :cost)
        LIMIT 1'; //множитель 0.001 в этом запросе означает, что точность схожести ресурсов 3 значащих цифры
        
        $entities = $db->query($sql, $resourceData, static::class);

        return $entities ? $entities[0] : null;       
    }

    public static function add(array $resourceData): BuildResource
    {
        $newBuildResource = new BuildResource;

        $newBuildResource->setResourceType($resourceData['resourceType']);
        $newBuildResource->setResourceCode($resourceData['resourceCode']);
        $newBuildResource->setName($resourceData['name']);
        $newBuildResource->setUnit($resourceData['unit']);
        $newBuildResource->setCost($resourceData['cost']);

        $newBuildResource->save();
        
        return $newBuildResource;
    }
}