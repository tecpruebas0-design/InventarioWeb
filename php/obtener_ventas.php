<?php
// inventario/php/obtener_ventas.php

session_start();
require_once 'conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Acceso denegado']);
    exit();
}

$sql = "SELECT v.id_venta, u.nombre_usuario, v.fecha, v.total
        FROM ventas v
        JOIN usuarios u ON v.id_usuario = u.id_usuario
        ORDER BY v.fecha DESC";

$result = $conn->query($sql);
$ventas = [];
if($result){
    while($row=$result->fetch_assoc()){
        $ventas[] = $row;
    }
    echo json_encode(['success'=>true,'ventas'=>$ventas]);
} else {
    echo json_encode(['success'=>false,'message'=>$conn->error]);
}

$conn->close();
exit();
?>
