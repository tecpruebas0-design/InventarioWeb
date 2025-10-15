<?php
// inventario/php/api_registrar_producto.php

require_once 'conexion.php';
header('Content-Type: application/json; charset=utf-8');

$response = array("status" => "", "message" => "");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos POST
    $codigo_barra = trim($_POST['codigo_barra'] ?? '');
    $nombre = trim($_POST['nombre_producto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_compra = floatval($_POST['precio_compra'] ?? 0);
    $precio_venta = floatval($_POST['precio_venta'] ?? 0);
    $stock = intval($_POST['stock_actual'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 10);

    // Validaciones
    if (empty($nombre) || $precio_compra <= 0 || $precio_venta <= 0 || $stock < 0) {
        $response["status"] = "error";
        $response["message"] = "Todos los campos obligatorios deben estar llenos y los precios/stock válidos.";
    } elseif ($precio_venta <= $precio_compra) {
        $response["status"] = "error";
        $response["message"] = "El precio de venta debe ser mayor que el precio de compra.";
    } else {
        // Preparar SQL
        $sql = "INSERT INTO productos (codigo_barra, nombre_producto, descripcion, precio_compra, precio_venta, stock_actual, stock_minimo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssdidi", $codigo_barra, $nombre, $descripcion, $precio_compra, $precio_venta, $stock, $stock_minimo);

            if ($stmt->execute()) {
                $response["status"] = "success";
                $response["message"] = "Producto registrado correctamente.";
            } else {
                if ($conn->errno === 1062) {
                    $response["status"] = "error";
                    $response["message"] = "El código de barra o nombre de producto ya existe.";
                } else {
                    $response["status"] = "error";
                    $response["message"] = "Error al registrar el producto: " . $stmt->error;
                }
            }
            $stmt->close();
        } else {
            $response["status"] = "error";
            $response["message"] = "Error de sistema al preparar la consulta.";
        }
    }

    $conn->close();
    echo json_encode($response);
    exit();
} else {
    $response["status"] = "error";
    $response["message"] = "Método no permitido.";
    echo json_encode($response);
    exit();
}
?>
