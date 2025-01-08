<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf8');
session_start();
error_reporting(0);
ini_set('display_errors', 'Off');

include('includes/db.php'); // Asegúrate de que establece $pdo como instancia de PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = $_POST['usuario'] ?? null;
    $contrasena = $_POST['contrasena'] ?? null;

    if ($nombre_usuario && $contrasena) {
        try {
            // Consulta para verificar usuario y contraseña
            $sql = "SELECT Id_Usuario, Nombre FROM UsuarioBlog WHERE Usuario = :usuario AND Contrasenia = :contrasena";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':usuario', $nombre_usuario, PDO::PARAM_STR);
            $stmt->bindParam(':contrasena', $contrasena, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $_SESSION['usuario'] = [
                    'id_usuario' => $result['Id_Usuario'],
                    'usuario' => $nombre_usuario,
                    'nombre' => $result['Nombre']
                ];
                echo json_encode([
                    'success' => true,
                    'message' => 'Inicio de sesión exitoso.',
                    'userId' => $result['Id_Usuario'] // <-- añadir esto
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas. Intente nuevamente.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Faltan datos en la solicitud.']);
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_COOKIE['cosmos'])) {
        $digest = $_COOKIE['cosmos'];
        try {
            // Consulta para validar digest de sesión
            $sql_cookie = "SELECT Usuario FROM UsuarioBlog WHERE reloginDigest = :digest";
            $stmt_cookie = $pdo->prepare($sql_cookie);
            $stmt_cookie->bindParam(':digest', $digest, PDO::PARAM_STR);
            $stmt_cookie->execute();

            $usuario = $stmt_cookie->fetchColumn();
            if ($usuario) {
                // Obtener datos completos del usuario
                $sql_completo = "SELECT Id_Usuario, Nombre FROM UsuarioBlog WHERE Usuario = :usuario";
                $stmt_completo = $pdo->prepare($sql_completo);
                $stmt_completo->bindParam(':usuario', $usuario, PDO::PARAM_STR);
                $stmt_completo->execute();

                $result = $stmt_completo->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $_SESSION['usuario'] = [
                        'id_usuario' => $result['Id_Usuario'],
                        'usuario' => $usuario,
                        'nombre' => $result['Nombre']
                    ];
                    echo json_encode([
                        'success' => true,
                        'message' => 'Inicio de sesión exitoso.',
                        'userId' => $result['Id_Usuario'] // <-- añadir esto
                    ]);
                } else {
                    echo json_encode(['session' => false, 'error' => 'No se encontraron datos del usuario.']);
                }
            } else {
                echo json_encode(['session' => false, 'error' => 'La cookie de sesión no es válida.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['session' => false, 'error' => 'Error al procesar la cookie de sesión: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['session' => false, 'error' => 'La cookie \'cosmos\' no está presente.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método HTTP no soportado.']);
}
?>
