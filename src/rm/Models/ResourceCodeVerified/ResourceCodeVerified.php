<?php

namespace rm\Models\ResourceCodeVerified;

use rm\Models\ActiveRecordEntity;
use rm\Services\Db;

class ResourceCodeVerified extends ActiveRecordEntity
{
    protected $verifiedResourceCode; //string
    
    protected $factResourceType; //string
    protected $factResourceCode; //string
    protected $factResourceName; //string
    protected $factUnit; //string
    
    //необходимый геттер на каждое поле
    protected function getVerifiedResourceCode()
    {
        return $this->verifiedResourceCode;
    }

    //создал приватный конструктор, чтобы напрямую через new никто не создал объект
    private function _construct()
    {
    
    }

    public static function repalaceResourceCode(array $resourceData): ?string
    {           
        $db = Db::getInstance();
        $sql = 'SELECT * FROM `' . static::getTableName() . '` WHERE 
        `factResourceType` = :factResourceType 
        AND `factResourceCode` = :factResourceCode 
        AND `factResourceName` = :factResourceName 
        AND `factUnit` = :factUnit
        LIMIT 1';

        $entities = $db->query($sql, $resourceData, static::class);

        if ($entities !== []) {
            return $entities[0]->getVerifiedResourceCode();
        }
        
        return null;
    }

    //таблица БД, которая совпадает с этим классом - наличие этой функции требует родительский класс
    protected static function getTableName():string
    {
        return 'resourceCodeVerified';
    }
}
