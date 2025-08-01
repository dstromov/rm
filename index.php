<?php
//echo '<br><br>';
//echo '<pre>';
//это индекс-файл для работы с задачей по парсингу ексель
// всегда парсить с 12 строки ексель. Т.е. с 11 элемента массива.

require_once __DIR__ . '/vendor/autoload.php';
function myAutoLoader(string $className)
{
//    echo 'Подключен класс: ', $className, '<br>';
    require_once __DIR__ . '\src\\' . $className . '.php';
}
spl_autoload_register('myAutoLoader');

// вызов главной страницы
if (isset($_GET['route']) === false) {
    $controller = new \rm\Controllers\MainController();
    $controller->main();
    return;
}



//поиск допустимого УРЛ
$route = $_GET['route'];
$routes = require __DIR__ . '\src\routes.php';

//если УРЛ найден, то запоминается контроллер и экшен
$controllerAndAction = $routes[$route];

$controllerName = $controllerAndAction[0];
$actionName = $controllerAndAction[1];

//вызов экшена
$controller = new $controllerName();

$controller->$actionName();



return;





//тестовый доступ в базу
use MyProject\Services\Db;

$db = Db::getInstance();
$sql = 'SELECT * FROM `data`';
$data = $db->query($sql);

echo '<pre>';
var_dump($data[0]);
var_dump($data[1]);




return;







