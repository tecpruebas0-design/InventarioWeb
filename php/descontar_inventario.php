<?php
// inventario/php/descontar_inventario.php - Descuenta una unidad del inventario por código de barra

session_start();
require_once 'conexion.php'; 

// 1. Verificar autenticación y método
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

// Asegúrate de que los datos necesarios estén presentes
$codigo_barra = $_POST['codigo_barra'] ?? '';
$cantidad_a_descontar = intval($_POST['cantidad'] ?? 0); // Debería ser 1

if (empty($codigo_barra) || $cantidad_a_descontar <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos de producto o cantidad inválidos.']);
    exit();
}

header('Content-Type: application/json');

// 2. Transacción: Buscar y descontar
$conn->begin_transaction();
$producto = null;

try {
    // A. Bloquear fila y obtener stock actual
    $sql_select = "SELECT id_producto, nombre_producto, stock_actual, stock_minimo FROM productos WHERE codigo_barra = ? FOR UPDATE";
    if ($stmt_select = $conn->prepare($sql_select)) {
        $stmt_select->bind_param("s", $codigo_barra);
        $stmt_select->execute();
        $result = $stmt_select->get_result();

        if ($result->num_rows === 0) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado con ese código de barra.']);
            exit();
        }

        $producto = $result->fetch_assoc();
        $nuevo_stock = $producto['stock_actual'] - $cantidad_a_descontar;
        $stmt_select->close();

        // B. Verificar stock
        if ($nuevo_stock < 0) {
            $conn->rollback();
            $mensaje_stock = ($producto['stock_actual'] == 0) 
                             ? "Stock agotado (0 unidades)." 
                             : "Solo quedan " . $producto['stock_actual'] . " unidades.";
            
            echo json_encode(['success' => false, 'message' => 'Error de Stock: ' . $mensaje_stock]);
            exit();
        }

        // C. Actualizar stock
        $sql_update = "UPDATE productos SET stock_actual = ? WHERE id_producto = ?";
        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param("ii", $nuevo_stock, $producto['id_producto']);
            
            if ($stmt_update->execute()) {
                $conn->commit();
                
                // Éxito: devolver el nombre del producto y el nuevo stock
                echo json_encode([
                    'success' => true, 
                    'message' => 'Descuento realizado con éxito.',
                    'nombre_producto' => $producto['nombre_producto'],
                    'stock_actual' => $nuevo_stock,
                    'stock_minimo' => $producto['stock_minimo']
                ]);
            } else {
                $conn->rollback();
                throw new Exception("Fallo al actualizar el stock: " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            $conn->rollback();
            throw new Exception("Fallo al preparar la actualización: " . $conn->error);
        }

    } else {
        $conn->rollback();
        throw new Exception("Fallo al preparar la consulta de selección: " . $conn->error);
    }

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de servidor: ' . $e->getMessage()]);
}

$conn->close();
exit();
?>