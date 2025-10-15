<?php
// inventario/views/descontar_stock.php

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
<title>Punto de Venta con Escáner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<style>
#interactive.viewport {
    width: 100%;
    height: 250px; 
    margin: 0 auto;
    position: relative;
    overflow: hidden; 
    background-color: #000;
}
#interactive.viewport > canvas,
#interactive.viewport > video {
    width: 100%;
    height: 100%;
    position: absolute;
    left: 0;
    top: 0;
    object-fit: cover; 
}
.autocomplete-list {
    position: absolute;
    z-index: 9999;
    background: #fff;
    border: 1px solid #ddd;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
}
.autocomplete-list div {
    padding: 8px 12px;
    cursor: pointer;
}
.autocomplete-list div:hover {
    background: #f0f0f0;
}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<main class="container-fluid mt-4">
<h1 class="mb-4">💳 Punto de Venta con escaner</h1>
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-upc-scan"></i> Escáner y Entrada Manual</h5>
            </div>
            <div class="card-body position-relative">
                <div id="interactive" class="viewport mb-3"></div>
                <div class="d-grid gap-2 mb-3">
                    <button id="startScannerBtn" class="btn btn-primary btn-lg">
                        <i class="bi bi-camera"></i> Iniciar Escáner
                    </button>
                    <input type="text" id="manualInput" class="form-control form-control-lg text-center" placeholder="Introduce código o nombre..." aria-label="Código o Nombre">
                    <div id="autocompleteList" class="autocomplete-list d-none"></div>
                </div>
                <div id="statusMessage" class="alert alert-info text-center mt-3">
                    Presiona "Iniciar Escáner" para leer el siguiente producto.
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Ticket de Venta</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">Cant.</th>
                                <th style="width: 50%;">Producto</th>
                                <th style="width: 15%;" class="text-end">Precio</th>
                                <th style="width: 15%;" class="text-end">Total</th>
                                <th style="width: 15%;"></th>
                            </tr>
                        </thead>
                        <tbody id="sale-list-body">
                            <tr id="empty-cart-row"><td colspan="5" class="text-center text-muted p-4">Escanea o ingresa un código para empezar la venta.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <h4 class="mb-0">TOTAL: <span id="grand-total" class="text-success fw-bold">$0.00</span></h4>
                <div>
                    <button id="confirmSaleBtn" class="btn btn-success btn-lg" disabled>
                        <i class="bi bi-check-circle"></i> Confirmar Venta
                    </button>
                    <button id="cancelSaleBtn" class="btn btn-danger btn-lg ms-2" disabled>
                        <i class="bi bi-x-circle"></i> Cancelar Venta
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const scannerContainer = document.getElementById('interactive');
const startBtn = document.getElementById('startScannerBtn');
const statusMsg = document.getElementById('statusMessage');
const manualInput = document.getElementById('manualInput');
const autocompleteList = document.getElementById('autocompleteList');
const saleListBody = document.getElementById('sale-list-body');
const grandTotalDisplay = document.getElementById('grand-total');
const confirmSaleBtn = document.getElementById('confirmSaleBtn');
const cancelSaleBtn = document.getElementById('cancelSaleBtn');

let isScannerRunning = false;
let saleItems = {};
let debounceTimeout;

// ESCÁNER
function startScanner() {
    if(isScannerRunning) return;
    statusMsg.className = 'alert alert-info text-center mt-3';
    statusMsg.textContent = "Iniciando cámara...";
    startBtn.disabled = true;

    Quagga.init({
        inputStream: { name: "Live", type: "LiveStream", target: scannerContainer, constraints: { facingMode: "environment" } },
        decoder: { readers: ["ean_reader","code_128_reader","upc_reader"] },
        locate: true
    }, function(err){
        if(err){
            console.error(err);
            statusMsg.className = 'alert alert-danger text-center mt-3';
            statusMsg.textContent = "ERROR: La cámara no está disponible.";
            startBtn.disabled = false;
            return;
        }
        Quagga.start();
        isScannerRunning = true;
        statusMsg.className = 'alert alert-success text-center mt-3';
        statusMsg.textContent = "Escáner activo. ¡Apunta al código!";
        startBtn.textContent = "Detener Escáner";
        startBtn.classList.replace('btn-primary','btn-warning');
        startBtn.disabled = false;
    });
}

function stopScanner() {
    if(isScannerRunning){
        Quagga.stop();
        isScannerRunning = false;
        statusMsg.className = 'alert alert-info text-center mt-3';
        statusMsg.textContent = "Escáner detenido. Presiona Iniciar para escanear el siguiente producto.";
        startBtn.textContent = "Iniciar Escáner";
        startBtn.classList.replace('btn-warning','btn-primary');
    }
}

Quagga.onDetected(result=>{
    const code = result.codeResult.code;
    if(code && isScannerRunning){
        stopScanner();
        addProductToSale(code);
    }
});

startBtn.addEventListener('click',()=>{ isScannerRunning ? stopScanner() : startScanner(); });

// AUTOCOMPLETE
manualInput.addEventListener('input', () => {
    const query = manualInput.value.trim();
    if(!query){ autocompleteList.classList.add('d-none'); return; }

    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        try{
            const res = await fetch(`../php/obtener_producto_por_codigo.php?query=${encodeURIComponent(query)}`);
            const data = await res.json();
            autocompleteList.innerHTML = '';
            if(data.success && data.productos.length>0){
                data.productos.forEach(p=>{
                    const div = document.createElement('div');
                    div.textContent = `${p.nombre_producto} - $${p.precio_venta} (Stock: ${p.stock_actual})`;
                    div.dataset.code = p.codigo_barra;
                    div.addEventListener('click', ()=>{
                        addProductToSale(p.codigo_barra);
                        manualInput.value='';
                        autocompleteList.classList.add('d-none');
                    });
                    autocompleteList.appendChild(div);
                });
                autocompleteList.classList.remove('d-none');
            } else autocompleteList.classList.add('d-none');
        } catch(e){ console.error(e); }
    },300);
});

// --- CARRITO ---
async function addProductToSale(code){
    if(saleItems[code]){
        if(saleItems[code].quantity+1 > saleItems[code].stock){
            Swal.fire('Stock Límite', `Solo quedan ${saleItems[code].stock} unidades de ${saleItems[code].name}`, 'warning');
            return;
        }
        saleItems[code].quantity += 1;
        renderSaleList();
        showSuccessScan(saleItems[code].name, saleItems[code].quantity);
    } else {
        await fetchProductDetails(code);
    }
}

async function fetchProductDetails(code){
    try{
        const res = await fetch(`../php/obtener_producto_por_codigo.php?codigo_barra=${encodeURIComponent(code)}`);
        const product = await res.json();
        if(product.success){
            const stock = parseInt(product.data.stock_actual);
            if(stock<=0){ Swal.fire('Stock Agotado', `${product.data.nombre_producto} está fuera de stock.`, 'error'); return; }
            saleItems[code] = {
                id: product.data.id_producto,
                name: product.data.nombre_producto,
                price: parseFloat(product.data.precio_venta),
                stock: stock,
                quantity: 1
            };
            renderSaleList();
            showSuccessScan(product.data.nombre_producto,1);
        } else {
            Swal.fire('Producto No Encontrado', `No se encontró un producto para el código ${code}.`, 'error');
        }
    } catch(e){ console.error(e); Swal.fire('Error de Red','No se pudo conectar con el servidor','error'); }
}

function renderSaleList(){
    let html=''; let grandTotal=0; const codes = Object.keys(saleItems);
    if(codes.length===0){
        html='<tr id="empty-cart-row"><td colspan="5" class="text-center text-muted p-4">Escanea o ingresa un código para empezar la venta.</td></tr>';
        confirmSaleBtn.disabled=true;
        cancelSaleBtn.disabled=true;
    } else {
        codes.forEach(code=>{
            const item = saleItems[code];
            const itemTotal = item.price*item.quantity;
            grandTotal+=itemTotal;
            html+=`<tr data-code="${code}">
                <td><input type="number" class="form-control form-control-sm text-center quantity-input" min="1" max="${item.stock}" value="${item.quantity}" data-code="${code}" style="width: 70px;"></td>
                <td>${item.name} <span class="badge bg-secondary">${code}</span> <span class="badge bg-warning text-dark ms-2">Stock: ${item.stock}</span></td>
                <td class="text-end">$${item.price.toFixed(2)}</td>
                <td class="text-end fw-bold">$${itemTotal.toFixed(2)}</td>
                <td><button class="btn btn-sm btn-outline-danger remove-item-btn" data-code="${code}"><i class="bi bi-x-lg"></i></button></td>
            </tr>`;
        });
        confirmSaleBtn.disabled=false;
        cancelSaleBtn.disabled=false;
    }
    saleListBody.innerHTML = html;
    grandTotalDisplay.textContent = `$${grandTotal.toFixed(2)}`;
}

saleListBody.addEventListener('click', e=>{
    if(e.target.closest('.remove-item-btn')){
        const code = e.target.closest('.remove-item-btn').dataset.code;
        delete saleItems[code];
        renderSaleList();
    }
});

saleListBody.addEventListener('change', e=>{
    if(e.target.classList.contains('quantity-input')){
        const code=e.target.dataset.code;
        const newQty=parseInt(e.target.value);
        if(newQty<1){ delete saleItems[code]; renderSaleList(); return; }
        if(newQty>saleItems[code].stock){ e.target.value=saleItems[code].stock; saleItems[code].quantity=saleItems[code].stock; Swal.fire('Límite de Stock','Cantidad máxima alcanzada','warning'); renderSaleList(); return; }
        saleItems[code].quantity=newQty;
        renderSaleList();
    }
});

// CONFIRMAR VENTA
confirmSaleBtn.addEventListener('click', async ()=>{
    if(Object.keys(saleItems).length===0){ Swal.fire('Venta Vacía','Agrega al menos un producto','warning'); return; }
    const itemsToSell = Object.keys(saleItems).map(code=>({ id_producto:saleItems[code].id, quantity:saleItems[code].quantity, codigo_barra:code }));
    Swal.fire({title:'Procesando Venta...',text:'Actualizando inventario...',icon:'info',allowOutsideClick:false,showConfirmButton:false,willOpen:()=>Swal.showLoading()});
    try{
        const res = await fetch('../php/procesar_venta.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({items:itemsToSell}) });
        const result = await res.json();
        if(result.success){
            Swal.fire({icon:'success',title:'¡Venta Confirmada!',html:`Se descontaron <strong>${result.items_processed}</strong> productos del inventario.`,timer:3000,showConfirmButton:false});
            saleItems={}; renderSaleList();
        } else { Swal.fire('Error en la Transacción',result.message,'error'); }
    } catch(e){ console.error(e); Swal.fire('Error de Red','No se pudo conectar con el servidor','error'); }
});

// CANCELAR VENTA
cancelSaleBtn.addEventListener('click', ()=>{
    Swal.fire({
        title: 'Cancelar Venta',
        text: '¿Estás seguro de que deseas cancelar la venta actual?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No'
    }).then((result) => {
        if(result.isConfirmed){
            saleItems={};
            renderSaleList();
            Swal.fire('Venta Cancelada','El carrito ha sido vaciado.','success');
        }
    });
});

function showSuccessScan(name, quantity){ 
    Swal.fire({toast:true,position:'top-end',icon:'success',title:`${name} x${quantity}`,showConfirmButton:false,timer:800}); 
}
document.addEventListener('DOMContentLoaded',()=>{ window.addEventListener('beforeunload', stopScanner); });
</script>
</body>
</html>
