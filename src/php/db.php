<?php

function getDbConnection() {
    $host = 'localhost';
    $username = 'root';
    $password = 'root';
    $database = 'wine';

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }

    return $conn;
}
?>
