<?php

namespace rm\Models\RmBuildResources;

use rm\Models\ActiveRecordEntity;
use rm\Models\BuildResources\BuildResource;
use rm\Models\Rms\Rm;
use rm\Models\UncCells\UncCell;

class RmBuildResource extends ActiveRecordEntity
{
    
    protected $uncTechnicalSpecificationId; // BuildResource
    protected $uncCellId; // UncCellRm   
    protected $quantity; //float
    protected $rmId; //Rm int
    protected $cts; //string

    //создал приватный конструктор, чтобы напрямую через new никто не создал объект
    private function _construct()
    {

    }

    //нужные сеттеры
    private function setBuildResource(BuildResource $buildResource) //TODO переделать на объект
    {
        $this->uncTechnicalSpecificationId = $buildResource->getId();
    }

    private function setUncCellId(int $uncCellId)
    {
        $this->uncCellId = $uncCellId;
    }

    private function setQuantity(float $quantity)
    {
        $this->quantity = $quantity;
    }

    private function setRm(Rm $rm)
    {
        $this->rmId = $rm->getId();
    }



    public static function add(array $resourceData): RmBuildResource
    {   
        $newBuildResource = new RmBuildResource();
      
        $newBuildResource->setBuildResource($resourceData['buildResource']);
        $newBuildResource->setUncCellId($resourceData['uncCellId']);
        $newBuildResource->setQuantity($resourceData['quantity']);
        $newBuildResource->setRm($resourceData['rm']);

        $newBuildResource->save();

        return $newBuildResource;
    }//конец метода add

    //таблица БД, которая совпадает с этим классом - наличие этой функции требует родительский класс
    protected static function getTableName():string
    {
        return 'uncCellUncTechnicalSpecification';
    }





}
