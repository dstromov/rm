<?php

//считать максимальный столбец, строку - аналогично
$worksheet->getHighestDataRow();


if (//проверка, что шапка начинается с 9 строки
    $dataArray[8][1] === 'Код ресурса'
    && $dataArray[8][2] === 'Наименование'
    && (
        $dataArray[11][1] === 'Затраты труда рабочих-строителей'
        || $dataArray[11][1] === 'Машины и механизмы'
        || $dataArray[11][1] === 'Оборудование '
        || $dataArray[11][1] === 'Оборудование'
        || $dataArray[11][1] === 'Материалы'
        || $dataArray[11][1] === 'Основные работы'
        )
 ) {
    echo $i, '_b9_', $dataArray[11][1], '_';

} elseif (//проверка, что шапка начинается с 10 строки
        $dataArray[9][1] === 'Код ресурса'
        && $dataArray[9][2] === 'Наименование'
        && (
            $dataArray[12][1] === 'Затраты труда рабочих-строителей'
            || $dataArray[12][1] === 'Машины и механизмы'
            || $dataArray[12][1] === 'Оборудование '
            || $dataArray[12][1] === 'Оборудование'
            || $dataArray[12][1] === 'Материалы'
            || $dataArray[12][1] === 'Основные работы'
            )
     ) {
        echo $i, '_b10_', $dataArray[12][1], '_';


} else {//если не найдена шапка или название вида работ
    echo $i, '_not_found_';
}

        {//строки для ведения лога в файл
            $countFileList = count($fileList);
            $parsingDate = date(DATE_RFC822);
            $file = fopen(self::$logPath, 'a'); //добавление логов включено
            fputs($file, "$parsingDate. Кол-во файлов в папке СТОРАДЖ для попытки парсинга: $countFileList" . PHP_EOL);
            fclose($file);
        }

                    {//отладочная печать в файл и на экран
                        echo '<br>', 'iteration: ', $i, ', файл: ', $fileName, '<br>';
                        // $file = fopen(self::$logPath, 'a');
                        // fputs($file, "$i|$fileName" . PHP_EOL);
                        // fclose($file);
                    }



                {//формирование массива для печати его в файл
                    $data[]= $worksheet->getCell('A' . $row)->getCalculatedValue();
                    $data[] = '|';
                    $data[]= $worksheet->getCell('B' . $row)->getCalculatedValue();
                    $data[] = '|';
                    $data[]= $worksheet->getCell('C' . $row)->getCalculatedValue();
                    $data[] = '|';
                    $data[]= $worksheet->getCell('D' . $row)->getCalculatedValue();
                    $data[] = '|';
                    $data[]= $worksheet->getCell('E' . $row)->getCalculatedValue();
                    $data[] = '|';
                    $data[]= $worksheet->getCell('J' . $row)->getCalculatedValue();
                    $data[] = '|';
                }



            file_put_contents(self::$logPath, $data, FILE_APPEND);
            $file = fopen(self::$logPath, 'a');
            fputs($file,  PHP_EOL);
            fclose($file);

            
        $file = fopen(self::$logPath, 'a');
        fputs($file, 'Парсинг окончен. Скрипт не упал по памяти и дошел до конца. Распознано файлов: ' . $i . PHP_EOL);
        fclose($file);