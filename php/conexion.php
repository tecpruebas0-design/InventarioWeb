<?php
// configuracion
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "inventario"; 

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

$conn->set_charset("utf8");

?>