<?php
// inventario/php/eliminar_producto.php

session_start();
require_once 'conexion.php'; 

// 1. Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Si no está logeado, devuelve un error JSON ya que se esperará una respuesta AJAX
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere autenticación.']);
    exit();
}

// 2. Verificar que la solicitud sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Método no permitido
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

// 3. Obtener y sanear el ID del producto
// Esperamos que el ID venga en la solicitud POST
$id_producto = intval($_POST['id_producto'] ?? 0);

if ($id_producto <= 0) {
    http_response_code(400); // Solicitud incorrecta
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido.']);
    exit();
}

// 4. Preparar la sentencia SQL para DELETE (Consulta Preparada)
$sql = "DELETE FROM productos WHERE id_producto = ?";

if ($stmt = $conn->prepare($sql)) {
    // "i" indica que se está ligando un parámetro de tipo integer
    $stmt->bind_param("i", $id_producto);
    
    if ($stmt->execute()) {
        // Verificar si se eliminó alguna fila
        if ($stmt->affected_rows > 0) {
            // Eliminación exitosa
            http_response_code(200); // OK
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Producto eliminado correctamente.']);
        } else {
            // El producto no existía
            http_response_code(404); // No encontrado
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'El producto con ID ' . $id_producto . ' no fue encontrado.']);
        }
    } else {
        // Error de ejecución SQL
        http_response_code(500); 
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la eliminación: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    // Error al preparar la consulta
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de sistema al preparar la consulta.']);
}

$conn->close();
exit();
?>