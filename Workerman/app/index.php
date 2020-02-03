<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Workerman\Worker;
use Workerman\Protocols\Websocket;

// Create a Websocket server
// 创建服务
$ws_worker = new Worker("websocket://127.0.0.1:2346");

// 最大通道数
$MaxConnectNum = 4;

// 4 processes
// 多线程
$ws_worker->count = 4;

// Emitted when new connection come
$ws_worker->onConnect = function ($connection) use ($ws_worker,$MaxConnectNum) {
    // BINARY_TYPE_BLOB utf8
    // BINARY_TYPE_ARRAYBUFFER  二进制

    // 超过链接 断开
    if(count($ws_worker->connections) > $MaxConnectNum){
        $connection->close();
    }

    $connection->websocketType = Websocket::BINARY_TYPE_BLOB;
    $connection->onWebSocketConnect = function ($connection, $http_header) {
        // 可以在这里判断连接来源是否合法，不合法就关掉连接
        // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket连接
        echo $_SERVER['HTTP_ORIGIN']."\n";
        // if($_SERVER['HTTP_ORIGIN'] != 'http://game.hiwebpage.com')
        // {
        //     $connection->close();
        // }
        // onWebSocketConnect 里面$_GET $_SERVER是可用的
        // var_dump($_GET, $_SERVER);
    };
    // 当前连接通道id
    $connection -> id = $ws_worker->id . $connection -> id;
    echo "New connection id ".$connection -> id."\n";


    $connection -> send('uuid&' . $connection -> id);
    $ids = [];
    foreach ($ws_worker->connections as $con) {
        array_push($ids, $con->id);
    }

    foreach ($ws_worker->connections as $con) {
        $con->send('connect&' . implode(',', $ids));
    }
};

// Emitted when data received
$ws_worker->onMessage = function ($connection, $data) use ($ws_worker) {
    $connection->websocketType = Websocket::BINARY_TYPE_BLOB;
    // Send hello $data
    foreach ($ws_worker->connections as $connection) {
        $connection->send('p&' . $data);
    }
};

// Emitted when connection closed
$ws_worker->onClose = function ($connection)use ($ws_worker) {
    echo "Connection closed ".$connection->id."\n";
    $num = count($ws_worker->connections);

    echo "num ".$num."\n";

    foreach ($ws_worker->connections as $con) {
        $con->send('close&' . $connection->id);
        $con -> send('surplusNumber&'.($num - 1));
    }

};

// Run worker
Worker::runAll();


// 注意php.ini disable_functions配置  可能会禁用某项函数