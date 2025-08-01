<?php

namespace rm\Models\UncCells;

use rm\Models\ActiveRecordEntity;

class UncCell extends ActiveRecordEntity
{
    protected $code //string

    //создал приватный конструктор, чтобы напрямую через new никто не создал объект
    private function _construct()
    {
    
    }

        //таблица БД, которая совпадает с этим классом - наличие этой функции требует родительский класс
        protected static function getTableName():string
        {
            return 'uncCell';
        }


}