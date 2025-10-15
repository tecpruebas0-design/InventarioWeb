<?php
// inventario/views/dashboard.php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}

// 1. Incluir el archivo de conexión a la base de datos
require_once '../php/conexion.php'; 

// Inicializar variables para las cantidades
$total_productos = 0;
$stock_critico = 0;
$valor_inventario = 0;
$productos_agotados = 0;
$ultimo_producto_nombre = "N/A";
$ultimo_producto_fecha = "N/A";

// 2. CONSULTAS CLAVE PARA EL DASHBOARD
try {
    // A. TOTAL de productos
    $sql_total = "SELECT COUNT(id_producto) AS total FROM productos";
    $resultado_total = $conn->query($sql_total);
    if ($resultado_total) {
        $total_productos = $resultado_total->fetch_assoc()['total'];
    }

    // B. STOCK CRÍTICO (stock_actual <= stock_minimo)
    $sql_critico = "SELECT COUNT(id_producto) AS critico FROM productos WHERE stock_actual <= stock_minimo AND stock_actual > 0";
    $resultado_critico = $conn->query($sql_critico);
    if ($resultado_critico) {
        $stock_critico = $resultado_critico->fetch_assoc()['critico'];
    }

    // C. VALOR TOTAL DEL INVENTARIO (Suma de (precio_compra * stock_actual))
    $sql_valor = "SELECT SUM(precio_compra * stock_actual) AS valor FROM productos";
    $resultado_valor = $conn->query($sql_valor);
    if ($resultado_valor) {
        // Formatear el valor para mostrarlo como moneda
        $valor_inventario_raw = $resultado_valor->fetch_assoc()['valor'];
        $valor_inventario = number_format($valor_inventario_raw, 2, '.', ',');
    }
    
    // D. PRODUCTOS AGOTADOS (stock_actual = 0)
    $sql_agotados = "SELECT COUNT(id_producto) AS agotados FROM productos WHERE stock_actual = 0";
    $resultado_agotados = $conn->query($sql_agotados);
    if ($resultado_agotados) {
        $productos_agotados = $resultado_agotados->fetch_assoc()['agotados'];
    }

    // E. ÚLTIMO PRODUCTO REGISTRADO
    $sql_ultimo = "SELECT nombre_producto, DATE_FORMAT(fecha_registro, '%d-%m-%Y %H:%i') AS fecha_formateada 
                   FROM productos 
                   ORDER BY fecha_registro DESC 
                   LIMIT 1";
    $resultado_ultimo = $conn->query($sql_ultimo);
    if ($resultado_ultimo && $resultado_ultimo->num_rows > 0) {
        $fila_ultimo = $resultado_ultimo->fetch_assoc();
        $ultimo_producto_nombre = $fila_ultimo['nombre_producto'];
        $ultimo_producto_fecha = $fila_ultimo['fecha_formateada'];
    }

} catch (Exception $e) {
    // Manejo de errores de base de datos
    error_log("Error al cargar datos del dashboard: " . $e->getMessage());
    $total_productos = "Error";
    $stock_critico = "Error";
    $valor_inventario = "Error";
    $productos_agotados = "Error";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Estilo para que las tarjetas con enlaces parezcan botones */
        .card-link {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .card-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    
    <?php include 'navbar.php'; // Incluimos la barra de navegación ?>

    <main class="container mt-4">

        <h2 class="mb-3">Información Rápida del Inventario</h2>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-5">
            
            <div class="col">
                <div class="card text-center text-bg-primary h-100">
                    <div class="card-body">
                        <h5 class="card-title">Productos Totales</h5>
                        <p class="card-text display-4"><?php echo $total_productos; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <a href="productos.php?estado=critico" class="text-decoration-none card-link">
                    <div class="card text-center text-bg-warning h-100">
                        <div class="card-body">
                            <h5 class="card-title">Stock Crítico ⚠️</h5>
                            <p class="card-text display-4"><?php echo $stock_critico; ?></p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="productos.php?estado=agotado" class="text-decoration-none card-link">
                    <div class="card text-center text-bg-danger h-100">
                        <div class="card-body">
                            <h5 class="card-title">Productos Agotados 🛑</h5>
                            <p class="card-text display-4"><?php echo $productos_agotados; ?></p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col">
                <div class="card text-center text-bg-success h-100">
                    <div class="card-body">
                        <h5 class="card-title">Valor Total (Costo)</h5>
                        <p class="card-text display-4">$<?php echo $valor_inventario; ?></p>
                    </div>
                </div>
            </div>
            
        </div>
        
        <h3 class="mb-3">Actividad Reciente</h3>
        <div class="card text-bg-light">
            <div class="card-body">
                <h5 class="card-title">Último Producto Registrado:</h5>
                <p class="card-text lead">
                    <span class="fw-bold text-primary"><?php echo htmlspecialchars($ultimo_producto_nombre); ?></span>
                    <br>
                    <small class="text-muted">Fecha de registro: <?php echo $ultimo_producto_fecha; ?></small>
                </p>
            </div>
        </div>
        
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>