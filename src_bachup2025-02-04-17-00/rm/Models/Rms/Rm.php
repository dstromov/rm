<?php

namespace rm\Models\Rms;

use rm\Models\ActiveRecordEntity;
use rm\Services\Db;

class Rm extends ActiveRecordEntity
{
    protected $path; //string
    protected $count; //float
    protected $cost; //float
    
    protected $worker; //int
    protected $machine; //int
    protected $equipment; //int
    protected $material; //int

    protected $mainWork; //int
    
    protected $cts; //string

    //создал приватный конструктор, чтобы напрямую через new никто не создал объект
    private function _construct()
    {

    }
    
    //необходимые на практике геттеры
    public function getCount(): float
    {
        return $this->count;
    }

    //сеттеры на каждое поле
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    public function setCount(float $count)
    {
        $this->count = $count;
    }
    
    public function setCost(float $cost)
    {
        $this->cost = $cost;
    }
    
    public function setWorker(int $worker)
    {
        $this->worker = $worker;
    }

    public function setMachine(int $machine)
    {
        $this->machine = $machine;
    }

    public function setEquipment(int $equipment)
    {
        $this->equipment = $equipment;
    }

    public function setMaterial(int $material)
    {
        $this->material = $material;
    }

    public function setMainWork(int $mainWork)
    {
        $this->mainWork = $mainWork;
    }

    //название соответствующей таблицы БД
    protected static function getTableName():string
    {
        return 'rm';
    }
    
    public static function add(array $rmData): Rm
    {
        $newRm = new Rm();
        $newRm->setPath($rmData['path']);
        $newRm->setCount($rmData['count']);
        $newRm->setCost($rmData['cost']);
        $newRm->save();

        return $newRm;
    }

    public function editMetaData(array $rmMetaData): void //ничего не возвращает
    {
        //проверка на вид файла "Основной" (должно быть строка "основные ресурсы" и не должно быть других видов)
        if (
            isset($rmMetaData['mainWork'])
            && !isset($rmMetaData['worker'])
            && !isset($rmMetaData['machine'])
            && !isset($rmMetaData['equipment'])
            && !isset($rmMetaData['material'])
        ) {
            $this->setMainWork($rmMetaData['mainWork']);
            $this->save();

            return;
        }

        //проверка на вид файла "Ресурсный" (должны быть 4 группы ресурсов и НЕ должно быть строки "основные ресурсы")
        if (
            !isset($rmMetaData['mainWork'])
            && isset($rmMetaData['worker'])
            && isset($rmMetaData['machine'])
            && isset($rmMetaData['equipment'])
            && isset($rmMetaData['material'])
        ) {
            //проверяем, чтобы разделы шли точно друг за другом
            if (
                $rmMetaData['worker'] < $rmMetaData['machine']
                && $rmMetaData['machine'] < $rmMetaData['equipment']
                && $rmMetaData['equipment'] < $rmMetaData['material']
                ) {
                    $this->setWorker($rmMetaData['worker']);
                    $this->setMachine($rmMetaData['machine']);
                    $this->setEquipment($rmMetaData['equipment']);
                    $this->setMaterial($rmMetaData['material']);
                        
                    $this->save();
                        
                    return;
                }           
        }//конец проверки на вид файла "Ресурсный"
        
        echo '!!! В этом файле проблемы с заголовками видов ресурсов !!! Дальнейший парсинг storage остановлен';
        
        exit();
    }// конец метода editMetaData
    
    //метод удаляет недозагруженные РМ. Все сущности удаляются благодаря выставленным ключам в БД
    public static function deleteRmWithNoType()
    {
        $sql = 'DELETE FROM `' . self::getTableName() . '` WHERE `worker` IS NULL AND `machine` IS NULL AND `equipment` IS NULL AND `material` IS NULL AND `mainWork` IS NULL';
        $db = Db::getInstance();
        $db->query($sql, $params = [], static::class);
    }
}
