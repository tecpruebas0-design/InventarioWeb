<?php
// inventario/views/ver_ventas.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ver Ventas - Inventario App</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="container mt-4">
<h1 class="mb-4">📋 Historial de Ventas</h1>

<div class="table-responsive">
    <table class="table table-striped table-hover" id="ventasTable">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        require_once '../php/conexion.php';
        $sql = "SELECT v.id_venta, u.nombre_usuario, v.fecha, v.total
                FROM ventas v
                JOIN usuarios u ON v.id_usuario = u.id_usuario
                ORDER BY v.fecha DESC";
        $result = $conn->query($sql);
        if($result && $result->num_rows>0){
            while($row=$result->fetch_assoc()){
                echo "<tr>
                    <td>{$row['id_venta']}</td>
                    <td>{$row['nombre_usuario']}</td>
                    <td>{$row['fecha']}</td>
                    <td>$".number_format($row['total'],2)."</td>
                    <td>
                        <button class='btn btn-sm btn-primary' onclick='verDetalle({$row['id_venta']})'>
                            <i class='bi bi-eye'></i> Ver
                        </button>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center text-muted'>No hay ventas registradas.</td></tr>";
        }
        $conn->close();
        ?>
        </tbody>
    </table>
</div>

<!-- Modal Detalle Mejorado con Total -->
<div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detalleModalLabel">Detalle de la Venta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="table-dark">
              <tr>
                <th>Producto</th>
                <th>Código</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody id="detalleBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <strong>Total de la Venta: $<span id="detalleTotal">0.00</span></strong>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function verDetalle(id_venta){
    try {
        const res = await fetch(`../php/obtener_detalles_venta.php?id_venta=${id_venta}`);
        const data = await res.json();
        if(data.success){
            const tbody = document.getElementById('detalleBody');
            tbody.innerHTML = '';
            if(data.detalles.length === 0){
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay detalles.</td></tr>';
            } else {
                data.detalles.forEach(item=>{
                    tbody.innerHTML += `<tr>
                        <td>${item.nombre_producto}</td>
                        <td>${item.codigo_barra ?? 'N/A'}</td>
                        <td>${item.cantidad}</td>
                        <td>$${parseFloat(item.precio_unitario).toFixed(2)}</td>
                        <td>$${parseFloat(item.subtotal).toFixed(2)}</td>
                    </tr>`;
                });
            }
            // Mostrar total de la venta
            let totalVenta = data.detalles.reduce((sum, item) => sum + parseFloat(item.subtotal), 0);
            document.getElementById('detalleTotal').textContent = totalVenta.toFixed(2);

            const modal = new bootstrap.Modal(document.getElementById('detalleModal'));
            modal.show();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch(e){
        console.error(e);
        Swal.fire('Error','Error al cargar detalles','error');
    }
}
</script>
</body>
</html>
