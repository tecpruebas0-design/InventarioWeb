<?php
// inventario/php/registrar_producto.php (CONFIRMADO)

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
    
    // 2. Obtener y sanear los datos
    $codigo_barra = trim($_POST['codigo_barra'] ?? '');
    $nombre = trim($_POST['nombre_producto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_compra = floatval($_POST['precio_compra'] ?? 0);
    $precio_venta = floatval($_POST['precio_venta'] ?? 0);
    $stock = intval($_POST['stock_actual'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 10);
    
    // 3. Validaciones básicas
    if (empty($nombre) || $precio_compra <= 0 || $precio_venta <= 0 || $stock < 0) {
        $message = 'Todos los campos obligatorios deben estar llenos y los precios/stock deben ser válidos.';
    } elseif ($precio_venta <= $precio_compra) {
        $message = 'El precio de venta debe ser mayor que el precio de compra.';
    } else {
        // 4. Preparar la sentencia SQL para inserción
        $sql = "INSERT INTO productos (codigo_barra, nombre_producto, descripcion, precio_compra, precio_venta, stock_actual, stock_minimo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            // "sssdidi" -> string, string, string, double, integer, integer
            $stmt->bind_param("sssdidi", 
                $codigo_barra, 
                $nombre, 
                $descripcion, 
                $precio_compra, 
                $precio_venta, 
                $stock, 
                $stock_minimo
            );
            
            if ($stmt->execute()) {
                $message = 'success_add';
            } else {
                 // Error 1062 es por duplicidad (código de barra o nombre ya existe)
                if ($conn->errno === 1062) {
                     $message = 'Error: El código de barra o nombre de producto ya existe.';
                } else {
                    $message = 'Error al registrar el producto: ' . $stmt->error;
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