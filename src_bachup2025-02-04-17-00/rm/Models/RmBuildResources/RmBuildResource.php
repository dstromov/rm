<?php

namespace rm\Models\RmBuildResources;

use rm\Models\ActiveRecordEntity;
use rm\Models\BuildResources\BuildResource;
use rm\Models\Rms\Rm;
use rm\Models\UncCells\UncCell;

class RmBuildResource extends ActiveRecordEntity
{
    
    protected $excelRow; //int
    protected $resourceType; //string
    // Ключи в БД видов работ:
    // worker - 'Затраты труда рабочих-строителей и машинистов'
    // machine - 'Машины и механизмы'
    // equipment - 'Оборудование'
    // material - 'Материалы'
    // mainWork - 'Основные работы'
    
    protected $resourceCode; //string
    protected $name; //string
    protected $quantity; //float
    protected $unit; //string
    
    protected $cost; //float
    protected $buildResourceId; // BuildResource
    protected $uncCellId; // UncCellRm
    protected $rmId; //Rm
    protected $cts; //string

    //создал приватный конструктор, чтобы напрямую через new никто не создал объект
    private function _construct()
    {

    }

    //сеттеры на каждое поле
    private function setResourceType(string $resourceType)
    {
        $this->resourceType = $resourceType;
    }

    private function setExcelRow(int $excelRow)
    {
        $this->excelRow = $excelRow;
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

    private function setQuantity(float $quantity)
    {
        $this->quantity = $quantity;
    }

    private function setCost(float $cost)
    {
        $this->cost = $cost;
    }

    private function setBuildResource(BuildResource $buildResource) //TODO переделать на объект
    {
        $this->BuildResourceId = $buildResource->getId();
    }

    private function setUncCellId(int $uncCellId)
    {
        $this->uncCellId = $uncCellId;
    }

    private function setRm(Rm $rm)
    {
        $this->rmId = $rm->getId();
    }

    //таблица БД, которая совпадает с этим классом - наличие этой функции требует родительский класс
    protected static function getTableName():string
    {
        return 'rm_build_resource';
    }

    public static function add(array $resourceData): RmBuildResource
    {   
        $newBuildResource = new RmBuildResource();

        $newBuildResource->setExcelRow($resourceData['excelRow']);
        $newBuildResource->setResourceType($resourceData['resourceType']);
        
        $newBuildResource->setResourceCode($resourceData['resourceCode']);
        $newBuildResource->setName($resourceData['name']);
        $newBuildResource->setQuantity($resourceData['quantity']);
        $newBuildResource->setUnit($resourceData['unit']);       
        $newBuildResource->setCost($resourceData['cost']);
        
        $newBuildResource->setBuildResource($resourceData['buildResource']);
        $newBuildResource->setUncCellId($resourceData['uncCellId']);
        $newBuildResource->setRm($resourceData['rm']);

        $newBuildResource->save();

        return $newBuildResource;
    }
}
