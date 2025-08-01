<?php

namespace rm\Controllers;

use rm\Services\Parser;
use rm\Models\Rms\Rm;

class MainController extends AbstractController
{    
    public function main() //рендеринг главной страницы
    { 
        $this->view->renderHTML('main.php');
    }

    public function parse() //парсинг хранилища
    {
        //удаляем из БД недозагруженные РМ
        Rm::deleteRmWithNoType();

        Parser::parseStorage('storage/все файлы/');
    }    
}
