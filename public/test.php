<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

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
