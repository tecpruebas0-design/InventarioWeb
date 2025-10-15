<?php
require_once 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$id_producto = intval($_POST['id_producto'] ?? 0);
$codigo_barra = trim($_POST['codigo_barra'] ?? '');
$nombre = trim($_POST['nombre_producto'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio_compra = floatval($_POST['precio_compra'] ?? 0);
$precio_venta = floatval($_POST['precio_venta'] ?? 0);
$stock = intval($_POST['stock_actual'] ?? 0);
$stock_minimo = intval($_POST['stock_minimo'] ?? 10);

if ($id_producto <= 0 || empty($nombre)) {
    echo json_encode(["success" => false, "message" => "Datos inválidos"]);
    exit;
}

$sql = "UPDATE productos SET 
            codigo_barra=?, nombre_producto=?, descripcion=?, 
            precio_compra=?, precio_venta=?, stock_actual=?, stock_minimo=?
        WHERE id_producto=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssdidii",
    $codigo_barra, $nombre, $descripcion,
    $precio_compra, $precio_venta, $stock, $stock_minimo, $id_producto
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Producto actualizado"]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
