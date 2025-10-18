<?php
// inventario/php/api_obtener_productos.php
require_once 'conexion.php'; 
header('Content-Type: application/json; charset=utf-8');

// --- Solo lista básica para la app ---
$sql = "SELECT id_producto, codigo_barra, nombre_producto, descripcion, precio_compra, precio_venta, stock_actual, stock_minimo 
        FROM productos ORDER BY nombre_producto ASC";

$result = $conn->query($sql);
$productos = [];

while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode([
    "status" => "success",
    "productos" => $productos
]);

$conn->close();
?>
