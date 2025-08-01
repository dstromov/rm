<?php

namespace rm\Models\UncCellRms;

use rm\Models\ActiveRecordEntity;
use rm\Models\Rms\Rm;

class uncCellRm extends ActiveRecordEntity
{
    protected $uncCellId; //UncCell int
    protected $rmName; //string
    protected $rmId; //Rm int
    
    //создал приватный конструктор, чтобы напрямую через new никто не создал объект
    private function _construct()
    {

    }

    //сеттер
    private function setRm(Rm $rm)
    {
        $this->rmId = $rm->getId();
    }

    //геттер
    public function getUncCellId(): int
    {
        return $this->uncCellId;
    }

    public function getFullPath (string $pathInStorage): string
    {
        return $pathInStorage . $this->rmName;
    }

    public function editRm(Rm $Rm)
    {
        $this->setRm($Rm);
        $this->save();
    }

    //таблица БД, которая совпадает с этим классом - наличие этой функции требует родительский класс
    protected static function getTableName():string
    {
        return 'uncCellRm';
    }

}//конец класса
