<?php
/**
 * Created by PhpStorm.
 * User: suhanyu
 * Date: 18/4/22
 * Time: 上午9:34
 */

require __DIR__ . "/../../vendor/autoload.php";

use \Workerman\Worker;
use Workerman\WebServer;
use Workerman\Protocols\Http;
use Libs\Core\Route\Router;
use NoahBuscher\Macaw\Macaw;

$encConfig = parse_ini_file(__DIR__ . "/../../.env");

$port = isset($encConfig['WEB_SITE_PORT'])&&$encConfig['WEB_SITE_PORT'] ? $encConfig['WEB_SITE_PORT'] : 8080;
$web = new WebServer('http://0.0.0.0:'.$port);
$web->addRoot('suhy.zyw.com',__DIR__.'/../../Frontend/dist');
$web->count = 3;

//接口访问
//针对请求做处理
$apiPort = isset($encConfig['API_SITE_PORT'])&&$encConfig['API_SITE_PORT'] ? $encConfig['API_SITE_PORT'] : 8081;
$apiServ = new Worker('http://0.0.0.0:'.$apiPort);
$apiServ->count = 2;
$iconContent = file_get_contents(__DIR__.'/../../Frontend/favicon.ico');

$apiServ->onWorkerStart = function () {
    //加载路由
    Macaw::get('/', 'Novel\Controllers\Access\LoginController@login');
    //加载和初始化配置相关

};

$apiServ->onMessage = function ($connection, $data)use($iconContent) {
    //1.处理 favicon.ico 文件
    if (isset($data['server']['REQUEST_URI']) &&
        strpos($data['server']['REQUEST_URI'],'favicon.ico') !== false)
    {
        $file_size = filesize(__DIR__.'/../../Frontend/favicon.ico');
        Http::header('Content-Type: image/x-icon');
        Http::header("Content-Length: $file_size");
        $connection->send($iconContent);
        return;
    }
    //2.针对请求，路由处理
    $refer = $data['server']['HTTP_REFERER'] ?? '';
    $responseStr = 'hello world!';
//    $result = Router::match(['get','match'],'Access','LoginController@login');
//    if (is_array($result)) {
//        $responseStr = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//    }

    ob_start();
    Macaw::dispatch();
    $responseStr = ob_get_clean();
    var_dump($responseStr);

    $connection->send($responseStr);
};

Worker::runAll();