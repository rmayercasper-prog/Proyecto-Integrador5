<?php
// 1. CONEXIÓN A LA BASE DE DATOS
$servername = "localhost";
$username = "root";
$password = "";
$db = "biblioteca_wed";

$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) { die("Error: " . $conn->connect_error); }

?>