<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf8');

session_start();
include('includes/db.php'); // $pdo instancia de PDO

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario está logueado:
if (!isset($_SESSION['usuario']['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$idHilo = $data['id_hilo'] ?? null;
$voto   = $data['voto'] ?? null; // 1 o -1

if (!$idHilo || !in_array($voto, [1, -1])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos para votar.']);
    exit;
}

$idUsuario = $_SESSION['usuario']['id_usuario'];

try {
    // Verificar si ya existe un voto del mismo usuario sobre este hilo
    $sqlCheck = "SELECT Voto FROM VotosHilo WHERE Id_Hilo = :hilo AND Id_Usuario = :usuario";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':hilo' => $idHilo, ':usuario' => $idUsuario]);
    $existeVoto = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existeVoto) {
        // Si el usuario ya votó antes, rechazamos el nuevo voto (o podríamos permitir cambiar el voto)
        echo json_encode(['success' => false, 'message' => 'Ya has votado este post.']);
        exit;
    }

    // Insertar el nuevo voto en VotosHilo
    $sqlInsert = "INSERT INTO VotosHilo (Id_Hilo, Id_Usuario, Voto) 
                  VALUES (:hilo, :usuario, :voto)";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        ':hilo' => $idHilo,
        ':usuario' => $idUsuario,
        ':voto' => $voto
    ]);

    // Actualizar la puntuación en la tabla HiloBlog, si estás usando Puntuacion directa:
    // UPDATE HiloBlog SET Puntuacion = Puntuacion + (1 o -1) WHERE Id_Hilo = :hilo
    $sqlUpdate = "UPDATE HiloBlog
                  SET Puntuacion = Puntuacion + :voto
                  WHERE Id_Hilo = :hilo";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([':voto' => $voto, ':hilo' => $idHilo]);

    echo json_encode(['success' => true, 'message' => 'Voto registrado.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
