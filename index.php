<?php
// inventario/index.php
session_start();

// Si el usuario ya está logueado, redirigir directamente al dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: views/dashboard.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        
        .login-container {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #343a40;
            padding: 15px;
        }
        
        .login-card {
            max-width: 420px;
            width: 100%; 
            
            max-height: 100%;
            overflow-y: auto; 
            
            
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4); 
            border: none; 
        }
    </style>
</head>
<body class="bg-dark-subtle"> 
    
    <div class="login-container">
        <div class="card login-card">
            
            <div class="text-center mb-5">
                <i class="bi bi-boxes text-primary" style="font-size: 3.5rem;"></i> 
                <h1 class="h3 fw-bold mt-3 mb-1">Control de Inventario</h1>
                <p class="text-muted">Inicie sesión con sus credenciales</p>
            </div>
            
            <form action="php/login.php" method="POST">
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Usuario o Email" required autofocus autocomplete="username">
                    <label for="username"><i class="bi bi-person-fill"></i> Usuario</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required autocomplete="current-password">
                    <label for="password"><i class="bi bi-lock-fill"></i> Contraseña</label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Entrar
                </button>
                
            </form>

            <p class="text-center mt-4 mb-0 text-muted">
                ¿No tienes una cuenta? <a href="registro_form.php" class="text-decoration-none fw-semibold">Regístrate aquí</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Lógica de SweetAlert2 para mostrar mensajes de error/éxito
        document.addEventListener('DOMContentLoaded', function() {
            // Manejar errores de Login
            <?php if (isset($_SESSION['login_error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Acceso',
                    text: '<?php echo htmlspecialchars($_SESSION['login_error']); ?>',
                    confirmButtonText: 'Entendido'
                });
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>

            // Manejar éxito de Registro
            <?php if (isset($_GET['message']) && urldecode($_GET['message']) === 'success'): ?>
                Swal.fire({
                    icon: 'success',
                    title: '¡Registro Exitoso!',
                    text: 'Tu cuenta ha sido creada. Por favor, inicia sesión.',
                    showConfirmButton: false,
                    timer: 3500 
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>