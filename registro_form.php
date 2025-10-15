<?php
// inventario/registro_form.php
session_start();

// Redirige al login si el registro fue exitoso para mostrar la alerta SweetAlert2
if (isset($_GET['message']) && urldecode($_GET['message']) === 'success') {
    header("Location: index.php?message=success");
    exit();
}

// Inicializar y manejar errores de la URL si el registro falla (ej: usuario ya existe)
$error_message = '';
if (isset($_GET['message'])) {
    $error_message = urldecode($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .register-container {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #343a40; 
            padding: 15px; /* Pequeño padding para asegurar espacio de respiro */
        }
        .register-card {
            max-width: 420px; /* Ancho ajustado a index.php */
            width: 100%; /* Asegura que tome el ancho máximo */
            
            /* --- CORRECCIONES CLAVE PARA EL DESBORDE --- */
            max-height: 100%; /* Asegura que no sea más alta que la ventana */
            overflow-y: auto; /* Permite scroll si el contenido es demasiado alto */
            /* ------------------------------------------- */
            
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4); 
            border: none;
        }
    </style>
</head>
<body class="bg-dark-subtle">
    
    <div class="register-container">
        <div class="card register-card">
            
            <div class="text-center mb-5">
                <i class="bi bi-person-plus-fill text-primary" style="font-size: 3.5rem;"></i> 
                <h1 class="h3 fw-bold mt-3 mb-1">Registro de Nuevo Usuario</h1>
                <p class="text-muted">Complete los campos para crear su cuenta</p>
            </div>
            
            <?php if ($error_message && $error_message !== 'success'): ?>
                <div class="alert alert-danger" role="alert">
                    ❌ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="php/registrar_usuario.php" method="POST">
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Nombre de Usuario" required autofocus autocomplete="off">
                    <label for="username"><i class="bi bi-person-fill"></i> Usuario</label>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required autocomplete="new-password">
                    <label for="password"><i class="bi bi-lock-fill"></i> Contraseña</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmar Contraseña" required autocomplete="new-password">
                    <label for="confirm_password"><i class="bi bi-lock-fill"></i> Confirmar Contraseña</label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-check-circle-fill"></i> Registrar
                </button>
                
            </form>

            <p class="text-center mt-4 mb-0 text-muted">
                ¿Ya tienes una cuenta? <a href="index.php" class="text-decoration-none fw-semibold">Inicia Sesión aquí</a>
            </p>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>