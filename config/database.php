<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "maan_ghafar_garments";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

?>