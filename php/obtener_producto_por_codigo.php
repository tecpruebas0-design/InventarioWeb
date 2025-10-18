<?php

session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

// 1. Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

// 2. Obtener parámetros
$codigo_barra = $_GET['codigo_barra'] ?? '';
$query = $_GET['query'] ?? '';

// 3. Búsqueda exacta por código de barra
if (!empty($codigo_barra)) {
    $sql = "SELECT id_producto, nombre_producto, codigo_barra, precio_venta, stock_actual 
            FROM productos WHERE codigo_barra = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $codigo_barra);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $producto = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $producto]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
        }
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta.']);
    }
    $conn->close();
    exit();
}

// 4. Búsqueda parcial para autocomplete (por código o nombre)
if (!empty($query)) {
    $likeQuery = "%$query%";
    $sql = "SELECT id_producto, nombre_producto, codigo_barra, precio_venta, stock_actual 
            FROM productos 
            WHERE codigo_barra LIKE ? OR nombre_producto LIKE ? 
            LIMIT 10";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $likeQuery, $likeQuery);
        $stmt->execute();
        $result = $stmt->get_result();
        $productos = [];
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        echo json_encode(['success' => true, 'productos' => $productos]);
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta.']);
    }
    $conn->close();
    exit();
}

// 5. Si no hay parámetros
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Parámetro inválido.']);
exit();
?>
