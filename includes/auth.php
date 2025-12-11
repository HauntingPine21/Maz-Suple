<?php
session_start();

// ==============================================
// CONFIGURACIÓN DE BASE DE DATOS (PDO + UTF8MB4)
// ==============================================
$host = 'localhost';
$dbname = 'MazSupledb';   // <-- ADAPTADO
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// ==============================================
// PROCESAR LOGIN
// ==============================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_input = trim($_POST['user'] ?? '');
    $pass_input = trim($_POST['pass'] ?? '');

    if ($user_input === '' || $pass_input === '') {
        $_SESSION['error_mensaje'] = "Debe ingresar usuario y contraseña.";
        header("Location: ../index.php");
        exit();
    }

    // Usuarios de la tabla: usuarios (MISMA ESTRUCTURA)
    $sql = "SELECT id, nombre_completo, username, password, rol 
            FROM usuarios 
            WHERE username = :user AND activo = 1 
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user' => $user_input]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {

        // Verificamos contraseña
        if (password_verify($pass_input, $usuario['password'])) {

            session_regenerate_id(true);

            // Guardamos datos relevantes en sesión
            $_SESSION['user'] = [
                'id'       => $usuario['id'],
                'username' => $usuario['username'],
                'nombre'   => $usuario['nombre_completo'],
                'rol'      => $usuario['rol']
            ];

            // Redirigir al dashboard
            header("Location: ../dashboard.php");
            exit();

        } else {
            $_SESSION['error_mensaje'] = "Contraseña incorrecta.";
            header("Location: ../index.php");
            exit();
        }

    } else {
        $_SESSION['error_mensaje'] = "Usuario no encontrado o inactivo.";
        header("Location: ../index.php");
        exit();
    }

} else {
    header("Location: ../index.php");
    exit();
}
?>
