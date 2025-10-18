<?php
// inventario/php/actualizar_inventario.php

session_start();
require_once 'conexion.php'; 

// 1. Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}

$redirect_to = '../views/productos.php'; 
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Obtener y sanear los datos, incluyendo el ID
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $codigo_barra = trim($_POST['codigo_barra'] ?? '');
    $nombre = trim($_POST['nombre_producto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_compra = floatval($_POST['precio_compra'] ?? 0);
    $precio_venta = floatval($_POST['precio_venta'] ?? 0);
    $stock = intval($_POST['stock_actual'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 10);
    
    // 3. Validaciones
    if ($id_producto <= 0) {
        $message = 'ID de producto inválido para la actualización.';
    } elseif (empty($nombre) || $precio_compra <= 0 || $precio_venta <= 0 || $stock < 0) {
        $message = 'Todos los campos obligatorios deben estar llenos y los precios/stock deben ser válidos.';
    } elseif ($precio_venta <= $precio_compra) {
        $message = 'El precio de venta debe ser mayor que el precio de compra.';
    } else {
        // 4. Preparar la sentencia SQL para UPDATE
        $sql = "UPDATE productos SET 
                    codigo_barra = ?, 
                    nombre_producto = ?, 
                    descripcion = ?, 
                    precio_compra = ?, 
                    precio_venta = ?, 
                    stock_actual = ?, 
                    stock_minimo = ? 
                WHERE id_producto = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // "sssdidii" -> 7 tipos de datos para los campos + 1 entero para el WHERE
            $stmt->bind_param("sssdidii", 
                $codigo_barra, 
                $nombre, 
                $descripcion, 
                $precio_compra, 
                $precio_venta, 
                $stock, 
                $stock_minimo,
                $id_producto
            );
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = 'success_edit';
                } else {
                    $message = 'No se realizó ningún cambio o el producto no existe.';
                }
            } else {
                // Error por codigo duplicado
                if ($conn->errno === 1062) {
                     $message = 'Error: El código de barra o nombre de producto ya existe.';
                } else {
                    $message = 'Error al actualizar el producto: ' . $stmt->error;
                }
            }
            $stmt->close();
        } else {
             $message = 'Error de sistema al preparar la consulta.';
        }
    }
    
    $conn->close();

    // 5. Redirigir con el mensaje
    header("Location: " . $redirect_to . "?status=" . urlencode($message));
    exit();

} else {
    // Si no es un POST, redirigir a la vista de productos
    header("Location: " . $redirect_to);
    exit();
}
?>