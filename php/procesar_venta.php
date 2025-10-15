<?php
session_start();
require_once 'conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lista de productos vacía.']);
    exit();
}

$id_usuario = intval($_SESSION['id_usuario']); // <-- ID del usuario que hace la venta

$conn->begin_transaction();
try {
    $total_venta = 0;

    // Calcular total y validar stock
    foreach ($items as $item) {
        $id_producto = intval($item['id_producto']);
        $cantidad = intval($item['quantity']);

        $stmt = $conn->prepare("SELECT nombre_producto, stock_actual, precio_venta FROM productos WHERE id_producto = ? FOR UPDATE");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) throw new Exception("Producto no encontrado ID: $id_producto");

        $prod = $res->fetch_assoc();
        if ($cantidad > $prod['stock_actual']) throw new Exception("Stock insuficiente para {$prod['nombre_producto']}");

        $total_venta += $cantidad * $prod['precio_venta'];
        $stmt->close();
    }

    // Insertar venta
    $stmt = $conn->prepare("INSERT INTO ventas (id_usuario, total) VALUES (?, ?)");
    $stmt->bind_param("id", $id_usuario, $total_venta);
    $stmt->execute();
    $id_venta = $stmt->insert_id;
    $stmt->close();

    // Insertar detalle y descontar stock
    foreach ($items as $item) {
        $id_producto = intval($item['id_producto']);
        $cantidad = intval($item['quantity']);

        $stmt = $conn->prepare("SELECT nombre_producto, stock_actual, precio_venta FROM productos WHERE id_producto = ? FOR UPDATE");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();
        $prod = $res->fetch_assoc();
        $stmt->close();

        $subtotal = $prod['precio_venta'] * $cantidad;

        $stmt = $conn->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", $id_venta, $id_producto, $cantidad, $prod['precio_venta'], $subtotal);
        $stmt->execute();
        $stmt->close();

        $nuevo_stock = $prod['stock_actual'] - $cantidad;
        $stmt = $conn->prepare("UPDATE productos SET stock_actual = ? WHERE id_producto = ?");
        $stmt->bind_param("ii", $nuevo_stock, $id_producto);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(['success'=>true, 'message'=>'Venta registrada con éxito', 'items_processed'=>count($items), 'id_venta'=>$id_venta]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Error al procesar la venta: '.$e->getMessage()]);
}

$conn->close();
exit();
?>
