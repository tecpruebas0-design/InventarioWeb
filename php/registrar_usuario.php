<?php
// inventario/php/registrar_usuario.php

// Incluir la conexión a la base de datos
require_once 'conexion.php'; 

// Verificar que la solicitud sea por el método POST (envío del formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Recibir y limpiar los datos del formulario
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Inicializar variables para manejo de errores/éxito
    $message = ''; 
    // Por defecto, redirigir al formulario de registro si hay un error
    $redirect_to = '../registro_form.php'; 

    // 2. Validaciones
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = 'Todos los campos son obligatorios.';
    } elseif ($password !== $confirm_password) {
        $message = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // 3. Generar el Hash seguro de la contraseña
        // PASSWORD_BCRYPT es la opción recomendada para hashing seguro
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // 4. Preparar la sentencia SQL para inserción (Consultas Preparadas)
        $sql = "INSERT INTO usuarios (nombre_usuario, clave) VALUES (?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            // "ss" indica que se están ligando dos parámetros de tipo string
            $stmt->bind_param("ss", $username, $hashed_password);
            
            if ($stmt->execute()) {
                // Registro exitoso
                $message = 'success'; // Código especial para indicar éxito
                $redirect_to = '../index.php'; // Redirigir al login
            } else {
                // Manejo específico del error de duplicidad (código 1062)
                if ($conn->errno === 1062) {
                     $message = 'El nombre de usuario ya existe. Elige otro.';
                } else {
                    $message = 'Error al registrar el usuario. Contacte al administrador.';
                    // Para depuración: $message .= ' (' . $stmt->error . ')';
                }
            }
            $stmt->close();
        } else {
             $message = 'Error de sistema al preparar la consulta.';
        }
    }
    
    // Cerrar la conexión
    $conn->close();

    // 5. Redirigir al formulario de registro o al login, pasando el mensaje por GET
    header("Location: " . $redirect_to . "?message=" . urlencode($message));
    exit();
    
} else {
    // Si acceden a este archivo directamente (no por POST), redirigir al formulario de registro
    header("Location: ../registro_form.php");
    exit();
}
?>