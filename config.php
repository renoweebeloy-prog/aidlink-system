<?php

$host = "sql7.freesqldatabase.com";
$dbname = "sql7827516";
$username = "sql7827516";
$password = "l8ruxRN4qW";
$port = 3306;

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
