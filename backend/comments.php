<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf8');

session_start();
include('includes/db.php'); // $pdo es la instancia PDO

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Crear nuevo comentario
        if (!isset($_SESSION['usuario']['id_usuario'])) {
            echo json_encode(['success' => false, 'message' => 'No has iniciado sesión.']);
            exit;
        }

        // Leer el cuerpo en formato JSON (o x-www-form-urlencoded)
        $data = json_decode(file_get_contents('php://input'), true);
        $idHilo = $data['id_hilo'] ?? null;
        $comentario = $data['comentario'] ?? null;

        if (!$idHilo || !$comentario) {
            echo json_encode(['success' => false, 'message' => 'Datos insuficientes para comentar.']);
            exit;
        }

        $idUsuario = $_SESSION['usuario']['id_usuario'];
        $nombreUsuario = $_SESSION['usuario']['nombre'];

        try {
            // Insertar en la base de datos
            $sql = "INSERT INTO ComentariosBlog (Id_Hilo, Id_Usuario, Comentario) 
                    VALUES (:hilo, :usuario, :comentario)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':hilo', $idHilo, PDO::PARAM_INT);
            $stmt->bindParam(':usuario', $idUsuario, PDO::PARAM_INT);
            $stmt->bindParam(':comentario', $comentario, PDO::PARAM_STR);
            $stmt->execute();

            // Obtener el Id_Comentario recién insertado si deseas retornarlo
            $idComentario = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Comentario agregado exitosamente.',
                'data' => [
                    'Id_Comentario' => $idComentario,
                    'Id_Usuario' => $idUsuario,
                    'NombreUsuario' => $nombreUsuario,
                    'Comentario' => $comentario,
                    // Agrega más campos si gustas
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el comentario: ' . $e->getMessage()]);
        }
        break;

    case 'GET':
        // Obtener comentarios de un hilo
        $idHilo = $_GET['id_hilo'] ?? null;
        if (!$idHilo) {
            echo json_encode(['success' => false, 'message' => 'Falta el parámetro id_hilo.']);
            exit;
        }
        try {
            $sql = "SELECT C.Id_Comentario, C.Id_Usuario, C.Comentario, C.FechaComentario, U.Nombre AS NombreUsuario
                    FROM ComentariosBlog C
                    INNER JOIN UsuarioBlog U ON C.Id_Usuario = U.Id_Usuario
                    WHERE C.Id_Hilo = :hilo
                    ORDER BY C.Id_Comentario ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':hilo', $idHilo, PDO::PARAM_INT);
            $stmt->execute();
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'comments' => $comments
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener comentarios: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Método no soportado.']);
        break;
}
