<?php
// inventario/php/obtener_detalles_venta.php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Acceso denegado']);
    exit();
}

$id_venta = intval($_GET['id_venta'] ?? 0);
if($id_venta <= 0){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'ID de venta inválido']);
    exit();
}

// Consulta a la tabla detalle_venta
$sql = "SELECT dv.cantidad, dv.precio_unitario, dv.subtotal, p.nombre_producto, p.codigo_barra
        FROM detalle_venta dv
        JOIN productos p ON dv.id_producto = p.id_producto
        WHERE dv.id_venta = ?
        ORDER BY dv.id_detalle ASC";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i",$id_venta);
    $stmt->execute();
    $result = $stmt->get_result();
    $detalles = [];
    while($row = $result->fetch_assoc()){
        $detalles[] = $row;
    }
    $stmt->close();
    echo json_encode(['success'=>true,'detalles'=>$detalles]);
} else {
    echo json_encode(['success'=>false,'message'=>'Error al preparar la consulta: '.$conn->error]);
}

$conn->close();
exit();
?>
