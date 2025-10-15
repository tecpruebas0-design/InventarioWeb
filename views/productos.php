<?php
// inventario/views/productos.php - COMPLETO CON CRUD, PAGINACIÓN, BÚSQUEDA Y ESCÁNER

session_start();
// Asegúrate de que esta ruta sea correcta para tu entorno
require_once '../php/conexion.php'; 
// Cierra la conexión inmediatamente si solo se usa para requerir (la lógica CRUD usa AJAX)
$conn->close(); 

// Validación de seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Obtener el estado de la URL (para SweetAlert2)
$status = isset($_GET['status']) ? urldecode($_GET['status']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 
    
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script> 
    
    <style>
        /* CORRECCIÓN CRUCIAL DE CSS: Aumentar la especificidad */
        /* Aplicar a la fila y a las celdas para anular estilos de .table-striped y .table-hover */
        .table tbody tr.stock-bajo,
        .table tbody tr.stock-bajo > td {
            background-color: #f8d7da !important; /* Rojo claro para alerta (¡Asegurado!) */
            font-weight: bold;
        }
        
        /* Asegura que el hover no reemplace el color de stock-bajo (opcional, pero recomendado) */
        .table-hover > tbody > tr.stock-bajo:hover > * {
            background-color: #f6c7cc !important; /* Tono ligeramente más oscuro al pasar el ratón */
        }
        
        /* Estilos del área de escaneo (CRUCIALES PARA MÓVIL) */
        #interactive_registro.viewport {
            width: 100%;
            height: 250px; 
            margin: 0 auto;
            position: relative;
            overflow: hidden; 
            background-color: #000;
        }
        #interactive_registro.viewport > canvas,
        #interactive_registro.viewport > video {
            width: 100%;
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
            object-fit: cover; 
        }
        .drawingBuffer {
            position: absolute;
            top: 0;
            left: 0;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; // Barra de navegación ?>

    <main class="container mt-4">
        <h1 class="mb-4">📦 Inventario de Productos</h1>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="col-md-5">
                <input type="text" id="searchInput" class="form-control" placeholder="Buscar por Nombre o Código de Barra...">
            </div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarProducto">
                + Agregar Nuevo Producto
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Precio Venta</th>
                        <th>Stock</th>
                        <th>Min. Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="productos-table-body">
                    <tr><td colspan="6" class="text-center">Cargando productos...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-center mt-3">
            <nav>
                <ul class="pagination" id="pagination-controls">
                </ul>
            </nav>
        </div>
    </main>

    <div class="modal fade" id="modalAgregarProducto" tabindex="-1" aria-labelledby="modalAgregarProductoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="../php/registrar_producto.php" method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="modalAgregarProductoLabel">Registrar Nuevo Producto</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre_producto" class="form-label">Nombre del Producto (*)</label>
                            <input type="text" class="form-control" id="nombre_producto" name="nombre_producto" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="codigo_barra" class="form-label">Código de Barra</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="codigo_barra" name="codigo_barra">
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#scannerModal">
                                    <i class="bi bi-camera"></i> Escanear
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="precio_compra" class="form-label">Precio Compra (*)</label>
                                <input type="number" step="0.01" class="form-control" id="precio_compra" name="precio_compra" required min="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="precio_venta" class="form-label">Precio Venta (*)</label>
                                <input type="number" step="0.01" class="form-control" id="precio_venta" name="precio_venta" required min="0.01">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stock_actual" class="form-label">Stock Inicial (*)</label>
                                <input type="number" class="form-control" id="stock_actual" name="stock_actual" required min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" value="10" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="modalEditarProductoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-editar-producto" action="../php/actualizar_inventario.php" method="POST">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="modalEditarProductoLabel">Editar Producto</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="edit-modal-body">
                        <div class="text-center p-4">Cargando datos del producto...</div>
                    </div>
                    <div class="modal-footer" id="edit-modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scannerModal" tabindex="-1" aria-labelledby="scannerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="scannerModalLabel">Escanear Código para Registro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="interactive_registro" class="viewport"></div>
                    <p id="scan-status" class="alert alert-info mt-3 text-center">Presiona Iniciar Escaneo...</p>
                </div>
                <div class="modal-footer">
                    <button id="startScannerModalBtn" class="btn btn-success">Iniciar Escaneo</button> 
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // --- Variables Globales ---
        let currentPage = 1;
        let currentSearch = '';
        let fetchController = null; 
        
        // Variables para el escáner (QuaggaJS)
        const scannerModal = document.getElementById('scannerModal');
        const startScannerModalBtn = document.getElementById('startScannerModalBtn');
        const scanStatus = document.getElementById('scan-status');
        let isScannerModalRunning = false;

        // --- LÓGICA DE ESCANEO PARA EL FORMULARIO DE REGISTRO (QuaggaJS) ---

        function startRegisterScanner() {
            if (isScannerModalRunning) {
                scanStatus.className = 'alert alert-success mt-3 text-center';
                scanStatus.textContent = "Cámara ya iniciada. Apunta al código.";
                startScannerModalBtn.style.display = 'none';
                return;
            }

            scanStatus.className = 'alert alert-info mt-3 text-center';
            scanStatus.textContent = "Iniciando cámara...";
            startScannerModalBtn.style.display = 'none'; 
            
            const scannerTarget = document.querySelector('#interactive_registro');

            Quagga.init({
                inputStream: {
                    name: "LiveRegister",
                    type: "LiveStream",
                    target: scannerTarget, 
                    constraints: {
                        width: { min: 640, ideal: 1280, max: 1920 },
                        height: { min: 480, ideal: 720, max: 1080 },
                        facingMode: "environment" // Usa la cámara trasera en móvil
                    },
                },
                locator: {
                    patchSize: "medium", 
                    halfSample: true
                },
                decoder: {
                    readers: ["ean_reader", "code_128_reader", "upc_reader", "code_39_reader"]
                },
                locate: true 
            }, function(err) {
                if (err) {
                    console.error("Error al inicializar Quagga:", err);
                    scanStatus.className = 'alert alert-danger mt-3 text-center';
                    scanStatus.textContent = "ERROR: La cámara no está disponible. Revisa permisos o HTTPS.";
                    startScannerModalBtn.style.display = 'block'; 
                    return;
                }
                
                Quagga.start();
                isScannerModalRunning = true;
                scanStatus.className = 'alert alert-success mt-3 text-center';
                scanStatus.textContent = "Cámara iniciada. Apunta al código (EAN, UPC, Code 128).";
            });
        }

        function stopRegisterScanner(code) {
            if (isScannerModalRunning) {
                // CORRECCIÓN: Stop debe llamarse solo si Quagga está realmente corriendo
                try {
                    Quagga.stop();
                } catch(e) {
                    // console.warn("Quagga ya estaba detenido o no se pudo detener.", e);
                }
                isScannerModalRunning = false;
            }
            
            if (code) {
                document.getElementById('codigo_barra').value = code;
                
                // Cierra el modal de escáner
                const scannerModalInstance = bootstrap.Modal.getInstance(scannerModal);
                if(scannerModalInstance) scannerModalInstance.hide();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Código Capturado',
                    text: `Código ${code} agregado al formulario.`,
                    timer: 1500, 
                    showConfirmButton: false
                }).then(() => {
                    // Vuelve a mostrar el modal principal después de cerrar el secundario
                    const mainModal = document.getElementById('modalAgregarProducto');
                    const mainModalInstance = bootstrap.Modal.getInstance(mainModal) || new bootstrap.Modal(mainModal);
                    // Asegúrate de que el modal principal se muestre, si no se cerró antes
                    if (!mainModal.classList.contains('show')) {
                        mainModalInstance.show(); 
                    }
                    
                    document.getElementById('descripcion').focus();
                });
            } 
            
            // Restablece el botón y el estado del escáner en el modal
            startScannerModalBtn.style.display = 'block'; 
            startScannerModalBtn.textContent = "Iniciar Escaneo";
            scanStatus.className = 'alert alert-info mt-3 text-center';
            scanStatus.textContent = "Presiona Iniciar Escaneo...";
        }
        
        Quagga.onDetected(function(result) {
            const code = result.codeResult.code;
            if (code && isScannerModalRunning) {
                stopRegisterScanner(code);
            }
        });

        // Eventos de control del modal Escáner
        scannerModal.addEventListener('shown.bs.modal', function () {
            // Detenemos el inicio automático si el botón está visible (significa que no ha iniciado aún)
            if(startScannerModalBtn.style.display !== 'none') {
                 // No hacer nada, esperar click en 'Iniciar Escaneo' o llamar a startRegisterScanner() aquí si quieres que sea automático
                 // Lo dejaremos automático con un pequeño retraso, pero mantenemos el botón por si hay fallos iniciales.
                setTimeout(startRegisterScanner, 200); 
            }
        });

        scannerModal.addEventListener('hide.bs.modal', function () {
            // Detenemos el escáner siempre al cerrar el modal
            stopRegisterScanner(null); 
        });

        startScannerModalBtn.addEventListener('click', function() {
            // Ya no es necesario el chequeo interno, la función startRegisterScanner lo maneja.
            startRegisterScanner();
        });
        
        // --- LÓGICA AJAX: TABLA, PAGINACIÓN Y BÚSQUEDA ---

        async function fetchProductos(pagina, busqueda) {
            const tableBody = document.getElementById('productos-table-body');
            const paginationControls = document.getElementById('pagination-controls');
            
            // Colspan se reduce de 7 a 6 al eliminar la columna ID
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-info" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';
            paginationControls.innerHTML = '';

            if (fetchController) {
                fetchController.abort();
            }
            fetchController = new AbortController();
            const signal = fetchController.signal;

            try {
                const response = await fetch(`../php/obtener_productos.php?pagina=${pagina}&busqueda=${encodeURIComponent(busqueda)}`, { signal });
                
                if (response.ok) {
                    const data = await response.json();
                    renderTable(data.productos);
                    renderPagination(data.pagina_actual, data.total_paginas);
                    
                    currentPage = data.pagina_actual;
                    currentSearch = busqueda;
                } else {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar productos.</td></tr>';
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Fetch error:', error);
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error de conexión al servidor.</td></tr>';
                }
            }
        }
        
        function renderTable(productos) {
            const tableBody = document.getElementById('productos-table-body');
            let html = '';

            if (productos.length === 0) {
                html = '<tr><td colspan="6" class="text-center">No se encontraron productos.</td></tr>';
            } else {
                productos.forEach(p => {
                    // Esta es la lógica que añade la clase 'stock-bajo' al TR
                    const claseStock = (p.stock_actual <= p.stock_minimo) ? 'stock-bajo' : '';
                    html += `
                        <tr class="${claseStock}" data-id="${p.id_producto}">
                            <td>${p.codigo_barra || ''}</td>
                            <td>${p.nombre_producto}</td>
                            <td>$${parseFloat(p.precio_venta).toFixed(2)}</td>
                            <td>${p.stock_actual}</td>
                            <td>${p.stock_minimo}</td>
                            <td>
                                <button class="btn btn-sm btn-info editar-btn" data-bs-toggle="modal" data-bs-target="#modalEditarProducto" data-id="${p.id_producto}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger eliminar-btn mt-1 mt-md-0" data-id="${p.id_producto}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            tableBody.innerHTML = html;
        }

        function renderPagination(actual, total) {
            const controls = document.getElementById('pagination-controls');
            let html = '';
            
            if (total <= 1) return controls.innerHTML = '';

            html += `<li class="page-item ${actual === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${actual - 1}">Anterior</a>
                     </li>`;

            for (let i = 1; i <= total; i++) {
                html += `<li class="page-item ${i === actual ? 'active' : ''}">
                             <a class="page-link" href="#" data-page="${i}">${i}</a>
                          </li>`;
            }

            html += `<li class="page-item ${actual === total ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${actual + 1}">Siguiente</a>
                     </li>`;
            
            controls.innerHTML = html;
        }

        // --- LÓGICA DE EDICIÓN ---
        function createEditFormHTML(data) {
            return `
                <input type="hidden" name="id_producto" value="${data.id_producto}">
                <div class="mb-3">
                    <label for="edit_nombre_producto" class="form-label">Nombre del Producto (*)</label>
                    <input type="text" class="form-control" id="edit_nombre_producto" name="nombre_producto" value="${data.nombre_producto}" required>
                </div>
                <div class="mb-3">
                    <label for="edit_codigo_barra" class="form-label">Código de Barra</label>
                    <input type="text" class="form-control" id="edit_codigo_barra" name="codigo_barra" value="${data.codigo_barra || ''}">
                </div>
                <div class="mb-3">
                    <label for="edit_descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="2">${data.descripcion || ''}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="edit_precio_compra" class="form-label">Precio Compra (*)</label>
                        <input type="number" step="0.01" class="form-control" id="edit_precio_compra" name="precio_compra" value="${parseFloat(data.precio_compra).toFixed(2)}" required min="0.01">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_precio_venta" class="form-label">Precio Venta (*)</label>
                        <input type="number" step="0.01" class="form-control" id="edit_precio_venta" name="precio_venta" value="${parseFloat(data.precio_venta).toFixed(2)}" required min="0.01">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="edit_stock_actual" class="form-label">Stock Actual (*)</label>
                        <input type="number" class="form-control" id="edit_stock_actual" name="stock_actual" value="${data.stock_actual}" required min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_stock_minimo" class="form-label">Stock Mínimo</label>
                        <input type="number" class="form-control" id="edit_stock_minimo" name="stock_minimo" value="${data.stock_minimo}" min="0">
                    </div>
                </div>
            `;
        }
        
        const modalEditarProducto = document.getElementById('modalEditarProducto');
        modalEditarProducto.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const productId = button.getAttribute('data-id');
            const modalBody = document.getElementById('edit-modal-body');
            const modalFooter = document.getElementById('edit-modal-footer');
            
            modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-info" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';

            try {
                // Endpoint único para obtener un producto por ID
                const response = await fetch(`../php/obtener_productos.php?id=${productId}`);
                const data = await response.json();

                if (response.ok && data.id_producto) {
                    modalBody.innerHTML = createEditFormHTML(data);
                    modalFooter.innerHTML = `
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">Guardar Cambios</button>
                    `;
                } else {
                    modalBody.innerHTML = `<div class="alert alert-danger">Error: ${data.error || 'No se pudo cargar el producto.'}</div>`;
                    modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
                }

            } catch (error) {
                console.error('Error en AJAX:', error);
                modalBody.innerHTML = '<div class="alert alert-danger">Error de conexión al servidor.</div>';
                modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
            }
        });
        
        // --- LÓGICA DE ELIMINACIÓN ---
        
        document.getElementById('productos-table-body').addEventListener('click', function(e) {
            if (e.target.closest('.eliminar-btn')) {
                e.preventDefault();
                const button = e.target.closest('.eliminar-btn');
                const productId = button.getAttribute('data-id');
                // Columna 2 es el nombre
                const productName = button.closest('tr').querySelector('td:nth-child(2)').textContent; 
                
                confirmDelete(productId, productName);
            }
        });

        function confirmDelete(id, name) {
            Swal.fire({
                title: `¿Estás seguro de eliminar el producto ${name}?`,
                text: "¡Esta acción es irreversible y el producto se borrará permanentemente!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, ¡Eliminar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeDelete(id);
                }
            });
        }

        async function executeDelete(id) {
            const formData = new FormData();
            formData.append('id_producto', id);

            try {
                const response = await fetch('../php/eliminar_producto.php', {
                    method: 'POST',
                    body: formData 
                });

                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        fetchProductos(currentPage, currentSearch); 
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Eliminación',
                        text: result.message
                    });
                }

            } catch (error) {
                console.error('Error de conexión:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Red',
                    text: 'No se pudo conectar con el servidor para eliminar el producto.'
                });
            }
        }

        // --- INICIALIZACIÓN Y EVENTOS GENERALES ---

        document.addEventListener('DOMContentLoaded', function() {
            // 1. Carga inicial de productos y mensajes
            fetchProductos(currentPage, currentSearch);
            showSweetAlert();
            
            // 2. Búsqueda en tiempo real (Debounce)
            const searchInput = document.getElementById('searchInput');
            let timer;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const busqueda = searchInput.value.trim();
                    if (busqueda !== currentSearch) {
                        fetchProductos(1, busqueda); 
                    }
                }, 300); 
            });
            
            // 3. Control de Paginación
            document.getElementById('pagination-controls').addEventListener('click', function(e) {
                if (e.target.classList.contains('page-link')) {
                    e.preventDefault();
                    const newPage = parseInt(e.target.getAttribute('data-page'));
                    if (newPage > 0 && newPage !== currentPage) {
                        fetchProductos(newPage, currentSearch);
                    }
                }
            });
        });
        
        // --- SweetAlert2 (Mensajes de Estado) ---

        function showSweetAlert() {
              const status = '<?php echo htmlspecialchars($status); ?>';
            if (status) {
                let icon = 'error';
                let title = 'Error';
                let text = status;

                if (status === 'success_add') {
                    icon = 'success';
                    title = '¡Producto Agregado!';
                    text = 'El nuevo producto se ha registrado correctamente.';
                } else if (status === 'success_edit') {
                    icon = 'success';
                    title = '¡Actualización Exitosa!';
                    text = 'Los cambios del producto han sido guardados.';
                }
                
                if (icon) {
                    Swal.fire({
                        icon: icon,
                        title: title,
                        text: text,
                        timer: (icon === 'success' ? 3000 : null),
                        showConfirmButton: (icon === 'error')
                    }).then(() => {
                        // Limpia el parámetro de la URL después de mostrar el mensaje
                        window.history.replaceState({}, document.title, window.location.pathname);
                        // Refresca la lista por si el mensaje vino de una acción que modificó los datos
                        fetchProductos(currentPage, currentSearch);
                    });
                }
            }
        }
    </script>
</body>
</html>