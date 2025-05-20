<?php
// filepath: /Users/lucaseduardo/wineServer/src/php/db.php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

function getDbConnection() {
    $host = $_ENV['DB_HOST'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $database = $_ENV['DB_DATABASE'];
    $port = $_ENV['DB_PORT'];

    $conn = new mysqli($host, $username, $password, $database, $port);

    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }

    return $conn;
}