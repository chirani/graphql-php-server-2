<?php

use App\Controller\GraphQL;

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . "/cors.php";



$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    require_once __DIR__ . '/../bootstrap.php';

    $r->get('/db-check', function () {

        $username = $_ENV['MYSQL_USER'];
        $password = $_ENV['MYSQL_PASSWORD'];
        $host = $_ENV['MYSQL_HOST'];
        $port = $_ENV['MYSQL_PORT'];
        $dbname = $_ENV['MYSQL_DB'];

        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            echo "Successfully connected to the managed database!";
        } catch (\PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    });

    $r->post('/graphql', function () use ($schema) {
        return GraphQL::handle($schema);
    });
});

$routeInfo = $dispatcher->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI']
);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        echo $handler($vars);
        break;
}
