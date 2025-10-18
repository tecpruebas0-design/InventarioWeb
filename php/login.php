<?php

session_start(); // Iniciar la sesión para gestión de usuarios
require_once 'conexion.php'; // Incluir la conexión a la base de datos

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Obtener y sanitizar los datos del formulario
    $user_input_username = $conn->real_escape_string($_POST['username'] ?? '');
    $user_input_password = $_POST['password'] ?? '';

    
    // 2. Preparar la consulta SQL para obtener el usuario y su hash de contraseña
    $sql = "SELECT id_usuario, nombre_usuario, clave FROM usuarios WHERE nombre_usuario = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $user_input_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashed_password = $user['clave'];

            // 3. Verificar la contraseña usando password_verify
            if (password_verify($user_input_password, $hashed_password)) {
                // Contraseña correcta: establecer variables de sesión
                $_SESSION['loggedin'] = true;
                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['nombre_usuario'] = $user['nombre_usuario'];

                // Redirigir al dashboard
                header("Location: ../views/dashboard.php");
                exit();
            } else {
                // Contraseña incorrecta
                $_SESSION['login_error'] = "Contraseña incorrecta.";
                // Redirigir a la página de inicio (index.php)
                header("Location: ../index.php"); 
                exit();
            }
        } else {
            // Usuario no encontrado
            $_SESSION['login_error'] = "Usuario no encontrado.";
            header("Location: ../index.php"); 
            exit();
        }

        $stmt->close();
    } else {
        // Error en la preparación de la consulta SQL
        $_SESSION['login_error'] = "Error de sistema al preparar la consulta.";
        header("Location: ../index.php"); 
        exit();
    }
    
    $conn->close();
} else {
    // Si no es una solicitud POST, redirigir al login
    header("Location: ../index.php");
    exit();
}
?>