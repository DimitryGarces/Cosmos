<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf8');

session_start();
error_reporting(0);
ini_set('display_errors', 'Off');

include('includes/db.php'); // Aquí $pdo debe ser tu instancia de PDO

try {
    // Verifica el método HTTP
    $method = $_SERVER['REQUEST_METHOD'];
    // Ejemplo rápido en posts.php (sección de DELETE)
    if ($method === 'DELETE') {
        parse_str(file_get_contents("php://input"), $delVars);
        $idHilo = $delVars['id_hilo'] ?? null;

        if (!isset($_SESSION['usuario']['id_usuario'])) {
            echo json_encode(['success' => false, 'message' => 'No has iniciado sesión.']);
            exit;
        }

        if (!$idHilo) {
            echo json_encode(['success' => false, 'message' => 'No se especificó el post a eliminar.']);
            exit;
        }

        $idUsuario = $_SESSION['usuario']['id_usuario'];

        // Eliminar solo si el post pertenece al usuario logueado
        $sql = "DELETE FROM HiloBlog WHERE Id_Hilo = :idHilo AND Id_Usuario = :idUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idHilo' => $idHilo, ':idUsuario' => $idUsuario]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Post eliminado exitosamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el post.']);
        }
        exit;
    }

    switch ($method) {
        case 'POST':
            // Crear un nuevo post
            // Necesitamos saber el Id_Usuario (desde la sesión) y recibir Titulo y Asunto desde el cuerpo de la petición
            if (!isset($_SESSION['usuario']['id_usuario'])) {
                echo json_encode(['success' => false, 'message' => 'No has iniciado sesión.']);
                exit;
            }

            // Leer los datos de la petición
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['titulo']) || !isset($data['asunto'])) {
                echo json_encode(['success' => false, 'message' => 'Datos insuficientes para crear el post.']);
                exit;
            }

            $idUsuario = $_SESSION['usuario']['id_usuario'];
            $titulo = $data['titulo'];
            $asunto = $data['asunto'];

            // Insertar en la base de datos
            $sql = "INSERT INTO HiloBlog (Titulo, Asunto, Id_Usuario) 
                    VALUES (:titulo, :asunto, :idUsuario)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':asunto', $asunto);
            $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
            $stmt->execute();

            // Retornar respuesta
            echo json_encode(['success' => true, 'message' => 'Post creado exitosamente.']);
            break;

        case 'GET':
            // Obtener la lista de posts
            // Podríamos obtener también el nombre del usuario que creó el post
            $sql = "SELECT 
                        H.Id_Hilo,
                        H.Titulo,
                        H.Asunto,
                        H.FechaCreacion,
                        H.Id_Usuario,
                        U.Nombre AS NombreUsuario
                    FROM HiloBlog H
                    INNER JOIN UsuarioBlog U ON H.Id_Usuario = U.Id_Usuario
                    ORDER BY H.Id_Hilo DESC"; // Ordenar del más reciente al más antiguo

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $hilos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'posts'   => $hilos
            ]);
            break;
        default:
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
