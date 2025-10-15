<?php
require_once 'conexion.php';

// Solo aceptar POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$id_producto = intval($_POST['id_producto'] ?? 0);

if ($id_producto <= 0) {
    echo json_encode(["success" => false, "message" => "ID inválido"]);
    exit;
}

$sql = "DELETE FROM productos WHERE id_producto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_producto);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Producto eliminado"]);
    } else {
        echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Error SQL: " . $stmt->error]);
}

$stmt->close();
$conn->close();
