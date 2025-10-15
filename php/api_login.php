<?php
// inventario/php/api_login.php
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = "SELECT id_usuario, nombre_usuario, clave FROM usuarios WHERE nombre_usuario = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['clave'])) {
                echo json_encode([
                    "status" => "success",
                    "id_usuario" => $user['id_usuario'],
                    "nombre_usuario" => $user['nombre_usuario']
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Contraseña incorrecta"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
        }
    }
}
$conn->close();
