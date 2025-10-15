<?php
// inventario/php/obtener_productos.php (Unificado para Lista, Búsqueda y Detalle)

session_start();
require_once 'conexion.php'; 

// 1. Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit();
}

// 2. Determinar el tipo de solicitud
$id_producto = intval($_GET['id'] ?? 0);

if ($id_producto > 0) {
    // A. Solicitud de DETALLE DE UN PRODUCTO (Usada por el Modal de Edición)
    handle_detalle_producto($conn, $id_producto);
    
} else {
    // B. Solicitud de LISTA PAGINADA Y/O BUSCADA (Usada por la Tabla Principal)
    handle_lista_productos($conn);
}

$conn->close();
exit();

// ----------------------------------------------------------------------
// FUNCIÓN B: LISTA, PAGINACIÓN, Y BÚSQUEDA
// ----------------------------------------------------------------------
function handle_lista_productos($conn) {
    $productos_por_pagina = 15;
    $pagina_actual = intval($_GET['pagina'] ?? 1);
    $busqueda = trim($_GET['busqueda'] ?? '');

    $offset = ($pagina_actual - 1) * $productos_por_pagina;

    // 1. Construcción de la consulta base y cláusula WHERE
    $sql_base = "SELECT id_producto, codigo_barra, nombre_producto, precio_venta, stock_actual, stock_minimo FROM productos";
    $where = "";
    $params = [];
    $types = "";

    if (!empty($busqueda)) {
        $where = " WHERE nombre_producto LIKE ? OR codigo_barra LIKE ?";
        $busqueda_like = "%" . $busqueda . "%";
        $params[] = &$busqueda_like;
        $params[] = &$busqueda_like;
        $types .= "ss";
    }

    // 2. Obtener el total de registros (con filtro de búsqueda)
    $sql_total = "SELECT COUNT(*) AS total FROM productos" . $where;
    $stmt_total = $conn->prepare($sql_total);

    if (!empty($params)) {
        $stmt_total->bind_param($types, ...$params);
    }
    $stmt_total->execute();
    $total_registros = $stmt_total->get_result()->fetch_assoc()['total'];
    $stmt_total->close();

    $total_paginas = ceil($total_registros / $productos_por_pagina);

    // 3. Obtener los productos de la página actual
    $sql_productos = $sql_base . $where . " ORDER BY nombre_producto ASC LIMIT ? OFFSET ?";
    $stmt_productos = $conn->prepare($sql_productos);

    $limit = $productos_por_pagina;
    $offset_ref = $offset; // Crear referencia
    
    // Crear la lista completa de parámetros para bind_param
    $all_params = $params;
    $all_params[] = &$limit;
    $all_params[] = &$offset_ref;
    $types_limit = $types . "ii";

    $stmt_productos->bind_param($types_limit, ...$all_params);
    $stmt_productos->execute();
    $result = $stmt_productos->get_result();

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    $stmt_productos->close();

    // 4. Devolver la respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'productos' => $productos,
        'pagina_actual' => $pagina_actual,
        'total_paginas' => $total_paginas,
        'total_registros' => $total_registros
    ]);
}


// ----------------------------------------------------------------------
// FUNCIÓN A: DETALLE DE UN PRODUCTO POR ID
// ----------------------------------------------------------------------
function handle_detalle_producto($conn, $id_producto) {
    $sql = "SELECT id_producto, codigo_barra, nombre_producto, descripcion, 
                   precio_compra, precio_venta, stock_actual, stock_minimo 
            FROM productos 
            WHERE id_producto = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $producto = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode($producto);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado.']);
        }
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al preparar la consulta.']);
    }
}
?>