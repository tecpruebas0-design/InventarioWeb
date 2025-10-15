<?php
header('Content-Type: application/json');
require __DIR__ . '/conexion.php';

if (isset($_POST['codigo_barra'])) {
    $codigo = trim($_POST['codigo_barra']);

    $sql = "SELECT id_producto, codigo_barra, nombre_producto, descripcion, precio_venta, stock_actual 
            FROM productos 
            WHERE codigo_barra LIKE ? 
            LIMIT 10"; // permitimos resultados parciales

    $stmt = $conn->prepare($sql);
    $like = "%$codigo%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $productos = [];
    while ($row = $resultado->fetch_assoc()) {
        $productos[] = $row;
    }

    if (count($productos) > 0) {
        echo json_encode(['success' => true, 'productos' => $productos]);
    } else {
        echo json_encode(['success' => false, 'productos' => []]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'productos' => []]);
}
?>
