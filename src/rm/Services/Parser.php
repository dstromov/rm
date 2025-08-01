<?php

namespace rm\Services;

use rm\Models\Rms\Rm;
use rm\Models\BuildResources\BuildResource;
use rm\Models\ResourceCodeVerified\ResourceCodeVerified;
use rm\Models\RmBuildResources\RmBuildResource;
use rm\Models\UncCellRms\UncCellRm;

class Parser
{
    //перечисление всех встречающихся листов во всех файлах, для оптимизации скрипта в целом
    private static $validSheets = [
        'Прил.5 Расчет СМР и ОБ',
        'Прил.1 Сравнит табл',
        'Прил.3',        
        'Прил. 3',
        'Прил.4 РМ',
        'Прил.6 Расчет ОБ',
        'Прил.10',
        'Прил. 10',
        'Прил.10 ',
        'ФОТи.тек.',
        'ФОТи.тек. ',
        'ФОТр.тек.',
        'ФОТр.тек. ',
        'ФОТинж 1кат.тек.',
        'ФОТинж 2кат.тек.',
    ];

    private static $logPath = __DIR__ . '/../../../parsingLog.txt';

    public static function parseStorage(string $storagePath)
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');       
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setLoadSheetsOnly(self::$validSheets);    
        
        $parseFile = uncCellRm::findIsNullByColumn('rmId');

        $i = 0;
        while ($parseFile !== null) {
            $i++;
            
            $fileName = $parseFile->getFullPath($storagePath);

            echo "iteration: $i, начало парсинга файла: $fileName", '<br>';

            $spreadsheet = $reader->load(__DIR__ . "/../../../$fileName");
            $worksheet = $spreadsheet->getSheetByName('Прил.5 Расчет СМР и ОБ');
            $highestRow  = $worksheet->getHighestDataRow();

            //пробегание непосредственно по листу Прил 5 для поиска кол-ва и итогов с записью в БД
            for ($row = $highestRow; $row >= 1; $row--) {//парсинг снизу листа вверх, т.к. итогова строка - одна из последних
                if ($worksheet->getCell('C' . $row)->getCalculatedValue() === 'ИТОГО ПОКАЗАТЕЛЬ НА ЕД. ИЗМ.') {
                    $newRmFile = Rm::add([ //создание в БД инф-ии о файле с распарсенными ключевыми значениями
                        'path' => $fileName,
                        'count' => $worksheet->getCell('E' . $row)->getCalculatedValue(), 
                        'cost' => $worksheet->getCell('J' . $row)->getCalculatedValue(),
                    ]);
                    break;
                }
                if ($row === 1) { //это значит, что парсер снизу дошел до первой строки и не нашел итогову строку
                    echo '!!! Не найдена итоговая строка "ИТОГО ПОКАЗАТЕЛЬ НА ЕД. ИЗМ." !!! ',
                        'Файлы до этого файла записались в БД и скрипт остановил работу';
                    exit();
                }
            }

            //пробегание непосредственно по листу Прил 5 для распознавания ресурсов
            $resourceType = '';
            $rowData = [];
            $rmMetaData = [];
            $j = 0;
            $k = 0;
            $t = 0;
            for ($row = 10; $row <= $highestRow; $row++) {
                
                //парсинг начинается с 10 строки, т.к. на этой строке (или на 1-3 ниже) расположен заголовок вида строительного ресурса
                //echo $row, ' ', $resourceType , '<br>';
                $rowData = [ //заполняем rowData для удобства чтения кода
                    'excelRow' => $row,
                    'columnA' => $worksheet->getCell('A' . $row)->getCalculatedValue(), //порядковый номер ресурса в РМ
                    'columnB' => $worksheet->getCell('B' . $row)->getCalculatedValue(), //код ресурса
                    'columnC' => $worksheet->getCell('C' . $row)->getCalculatedValue(), //имя ресурса
                    'columnD' => $worksheet->getCell('D' . $row)->getCalculatedValue(), //ед.изм. ресурса
                    'columnE' => $worksheet->getCell('E' . $row)->getCalculatedValue(), //кол-во ресурса
                    'columnI' => $worksheet->getCell('J' . $row)->getCalculatedValue(), //стоимость за единицу ресурса
                    'columnJ' => $worksheet->getCell('J' . $row)->getCalculatedValue(), //стоимость ресурса
                ];

                {//обработчики распарсенных данных перед записью
                    //конвертация нужна, т.к. встречается число в виде текста в ексель, PHP видит зяпятую как текст и отбрасывает дробную часть, см. Т1
                    if (is_string($rowData['columnE'])) $rowData['columnE'] = (float) str_replace(',', '.', $rowData['columnE']);

                    //удаление пробелов в начале и конце
                    if ($rowData['columnB'] !== null) $rowData['columnB'] = trim($rowData['columnB']);
                    if ($rowData['columnC'] !== null) $rowData['columnC'] = trim($rowData['columnC']);
                    if ($rowData['columnD'] !== null) $rowData['columnD'] = trim($rowData['columnD']);                   
                    
                    //замена переносов строк на пробелы
                    if (is_string($rowData['columnC']) && mb_stripos($rowData['columnC'], "\n")) $rowData['columnC'] = strtr($rowData['columnC'], "\n",' ');
                    if (is_string($rowData['columnB']) && mb_stripos($rowData['columnB'], "\n")) $rowData['columnB'] = strtr($rowData['columnB'], "\n",' ');
                    if (is_string($rowData['columnD']) && mb_stripos($rowData['columnD'], "\n")) $rowData['columnD'] = strtr($rowData['columnD'], "\n",' ');

                    //замена разношерстных ед.изм. на стандартные написания
                    if ($rowData['columnD'] === 'уп') $rowData['columnD'] = 'уп.';
                    
                    if ($rowData['columnD'] === 'руб,' || $rowData['columnD'] === 'руб') $rowData['columnD'] = 'руб.';
                    
                    if ($rowData['columnD'] === 'М.') $rowData['columnD'] = 'м';
                    if ($rowData['columnD'] === '1 М3') $rowData['columnD'] = '1 м3';
                    
                    if ($rowData['columnD'] === '1000 шт') $rowData['columnD'] = '1000 шт.';
                    if ($rowData['columnD'] === '100 шт' || $rowData['columnD'] === '100 шт,') $rowData['columnD'] = '100 шт.';
                    
                    if ($rowData['columnD'] === '10 шт'
                        || $rowData['columnD'] === '10 шт,'
                        || $rowData['columnD'] === '10шт'
                        || $rowData['columnD'] === '10шт.'
                    ) $rowData['columnD'] = '10 шт.';
                    
                    if ($rowData['columnD'] === 'шт'
                        || $rowData['columnD'] === 'шт,'
                        || $rowData['columnD'] === '1шт.'
                        || $rowData['columnD'] === '1шт'
                        || $rowData['columnD'] === '1 шт.'
                        || $rowData['columnD'] === 'шт1'
                        || $rowData['columnD'] === 'ШТ'
                        || $rowData['columnD'] === 'щт'
                    ) $rowData['columnD'] = 'шт.';
                    
                    if ($rowData['columnD'] === 'КОМПЛЕКТ'
                        || $rowData['columnD'] === 'комплект'
                        || $rowData['columnD'] === 'компл'
                        || $rowData['columnD'] === 'комп.'
                        || $rowData['columnD'] === 'комп'
                        || $rowData['columnD'] === 'к-т'
                        || $rowData['columnD'] === 'К-Т'
                        || $rowData['columnD'] === 'к-т.'
                        || $rowData['columnD'] === '1 компл'
                    ) $rowData['columnD'] = 'компл.';
                    
                    if ($rowData['columnD'] === '100 КОМПЛ.') $rowData['columnD'] = '100 компл.';
                    
                    if ($rowData['columnD'] === '1-ф компл.' || $rowData['columnD'] === '1 фазн.компл.') $rowData['columnD'] = '1 фазн. компл.';
                    if ($rowData['columnD'] === '3-ф. компл.' || $rowData['columnD'] === '3-х фазн. к-т') $rowData['columnD'] = '3 фазн. компл.';
                    
                    if ($rowData['columnD'] === 'чел.-ч.' || $rowData['columnD'] === 'чел.час') $rowData['columnD'] = 'чел.-ч';
                    
                    if ($rowData['columnD'] === 'маш.час'
                        || $rowData['columnD'] === 'маш.-ч.'
                        || $rowData['columnD'] === 'маш,-ч'
                    ) $rowData['columnD'] = 'маш.-ч';
                    
                    if ($rowData['columnD'] === 'П.М.'
                        || $rowData['columnD'] === 'п,м'
                        || $rowData['columnD'] === 'п.м'
                    ) $rowData['columnD'] = 'п.м.';
                    
                    //перезаписываем кол-во на 1, заполняем пустую размерность
                    if ($rowData['columnJ'] > 0
                        && $rowData['columnC'] === 'Камеральные работы'
                        && $rowData['columnD'] === 'руб.'
                        && $rowData['columnE'] === null
                    ) $rowData['columnE'] = 1;
                    
                    if ($rowData['columnJ'] > 0
                        && $rowData['columnC'] === 'Полевые работы'
                        && $rowData['columnD'] === 'руб.'
                        && $rowData['columnE'] === null
                    ) $rowData['columnE'] = 1;

                    if ($rowData['columnJ'] > 0
                        && $rowData['columnC'] === 'Расходы по внутреннему транспорту (6.25% от полевых работ)'
                        && $rowData['columnD'] === 'руб.'
                    ) $rowData['columnE'] = 1;
                    if ($rowData['columnJ'] > 0
                        && $rowData['columnC'] === 'Расходы по внешнему транспорту (11,50% от полевых работ)'
                        && $rowData['columnD'] === 'руб.'
                    ) $rowData['columnE'] = 1;

                    if ($rowData['columnJ'] > 0
                        && $rowData['columnC'] === 'Расходы на содержание базы отряда'
                        && $rowData['columnD'] === 'руб.'
                    ) $rowData['columnE'] = 1;
                    
                    if ($rowData['columnJ'] > 0
                        && $rowData['columnC'] === 'Расходы по организации и ликвидации работ на объекте'
                        && $rowData['columnD'] === 'руб.'
                    ) $rowData['columnE'] = 1;

                    if ($rowData['columnJ'] > 0
                        && $rowData['columnC'] === 'Затраты на медицинское обеспечение работ'
                        && $rowData['columnD'] === null
                        && $rowData['columnE'] === null
                    ) {
                        $rowData['columnD'] = 'руб.';
                        $rowData['columnE'] = 1;
                    }
                }                

                {//назначать дальнейшим строкам вид ресурса
                    if ($rowData['columnB'] === 'Затраты труда рабочих-строителей') {
                        $resourceType = 'worker';
                        $rmMetaData['worker'] = $rowData['excelRow'];
                    }

                    if ($rowData['columnB'] === 'Машины и механизмы') {
                        $resourceType = 'machine';
                        $rmMetaData['machine'] = $rowData['excelRow'];
                    }

                    if ($rowData['columnB'] === 'Оборудование') {
                        $resourceType = 'equipment';
                        $rmMetaData['equipment'] = $rowData['excelRow'];
                    }

                    if ($rowData['columnB'] === 'Оборудование (состав оборудования и его ценовая информация указаны справочно на основании ориентировочных цен. При расчете показателя по результатам мониторинга эти данные будут уточнены)') {
                        $resourceType = 'equipment';
                        $rmMetaData['equipment'] = $rowData['excelRow'];
                    }

                    if ($rowData['columnB'] === 'Материалы') {
                        $resourceType = 'material';
                        $rmMetaData['material'] = $rowData['excelRow'];
                    }
                    
                    if ($rowData['columnB'] === 'Основные работы') {
                        $resourceType = 'mainWork';
                        $rmMetaData['mainWork'] = $rowData['excelRow'];
                    }
                }

                {//переход к следующей строке или сразу к следующему файлу
                    //останавливаем цикл по файлу, т.к. с этой строки и ниже уже нет нужных данных
                    if ($rowData['columnC'] === 'Итого прочие материалы') break; 
                    if ($rowData['columnB'] === 'Итого прочие Материалы') break;  //для некоторых Ж3
                    if ($rowData['columnC'] === 'Итого по разделу «Материалы»') break;  //для некоторых И5
                    if ($rowData['columnC'] === 'Итого по разделу «Основные работы»') break;  //для Б6, П12
                    
                    if ($row === $highestRow) {
                        echo '!!! Парсер дошел до конца листа и не нашел строку окончания листа !!! Дальнейший парсинг остановлен';
                        exit();
                    }

                    if ($resourceType === '') continue; //значит, что алгоритм еще не нашел первый заголовок
                    if ($rowData['columnA'] === null) continue; //парсим только строки с ресурсами, т.е. где в первом столбце есть нумерация
           
                    //встречается ошибочная нумерация в строках-заголовках
                    if ($rowData['columnC'] === 'Итого основное оборудование') continue;  //для И15
                    if ($rowData['columnC'] === 'Итого основные материалы') continue;  //для А5
                    if ($rowData['columnC'] === 'Итого основные машины и механизмы') continue;  //для А5
                    if ($rowData['columnC'] === "Итого основные машины и механизмы  (с коэффициентом на демонтаж 0,7)") continue;  //для М2
                    if ($rowData['columnC'] === 'Итого прочее оборудование') continue;  //для У4
                    if ($rowData['columnC'] === 'Итого прочие машины и механизмы') continue;  //для У4
                    if ($rowData['columnC'] === 'Итого по разделу "Затраты труда рабочих-строителей"') continue;  //для У4
                    if ($rowData['columnC'] === 'Итого по разделу «Оборудование»') continue;  //для У4

                    if ($rowData['columnJ'] === null) continue; //встречаются ненулевые ресурсы, но без формул, на практике это мелочевка, поэтому
                    //не правим РМ, а пропускаем (или потому что такая редакция файлов согласована с МЭ)
                    if ($rowData['columnJ'] === (float) 0) continue;  //для Л1; не записываем нулевые ресурсы, т.к. возникают проблемы
                    // с формированием справочника ресурсов
                }
                
                {//замена кодов ресурса с ошибками на верифицированные и хранящиеся в БД
                    $replacedResourceCode = ResourceCodeVerified::repalaceResourceCode([
                        'factResourceType' => $resourceType,
                        'factResourceCode' => $rowData['columnB'],
                        'factResourceName' => $rowData['columnC'],
                        'factUnit' => $rowData['columnD'],
                    ]);

                    if ($replacedResourceCode !== null) {
                        $t++;
                        $rowData['columnB'] = $replacedResourceCode;
                    }
                }

                //создаем сущность распарсенного строительного ресурса
                $buildResourceQuantity = $rowData['columnE'] / $newRmFile->getCount();
                $buildResourceData = [
                    'resourceType' => $resourceType,
                    'resourceCode' => $rowData['columnB'],
                    'name' => $rowData['columnC'],
                    'unit' => $rowData['columnD'],
                    'cost' => ($rowData['columnJ'] / $newRmFile->getCount()) / $buildResourceQuantity,
                ];

                $newBuildResource = BuildResource::findBuildResource($buildResourceData);

                //запись нового ресурса в справочник ресурсов
                if ($newBuildResource === null) {
                    $k++;
                    $newBuildResource = BuildResource::add($buildResourceData);
                }

                $j++;
                RmBuildResource::add([ //запись в БД одного ресурса
                    'excelRow' => $row,
                    'resourceType' => $resourceType,
                    'resourceCode' => $rowData['columnB'],
                    'name' => $rowData['columnC'],
                    'quantity' => $rowData['columnE'] / $newRmFile->getCount(),
                    'unit' => $rowData['columnD'],
                    'cost' => $rowData['columnJ'] / $newRmFile->getCount(),
                    'buildResource' => $newBuildResource,
                    'uncCellId' => $parseFile->getUncCellId(),
                    'rm' => $newRmFile,
                ]);
            }//конец перебора одного файла

            echo "Распознано и сохранено ресурсов по РМ: $j; ",
                "заменено кодов ресурсов на верифицированные: $t; ",
                "добавлено новых ресурсов в справочник: $k",
                '<br><br>';

            //дозапись распознанной РМ в справочник связи РМ-УНЦ
            $parseFile->editRm($newRmFile);

            //дозапиcь мета-данных по листу ПРИЛ 5 файла !! Внимание!! это действие должно быть строго последнее
            // по файлу, т.к. по этому действию идет проверка, дораспознан файл или нет
            $newRmFile->editMetaData($rmMetaData);
            
            //перезапрос для цикла while остались ли задачи для парсинга
            $parseFile = uncCellRm::findIsNullByColumn('rmid');

        }//конец цикла while

        echo '<br>';
        echo 'Парсинг окончен. Скрипт не упал по памяти и/или времени и дошел до конца. Распознано файлов: ', $i;
    } //конец метода класса

} //конец класса
