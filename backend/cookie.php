<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
error_reporting(0);
ini_set('display_errors', 'Off');

include('includes/generateDigest.php');

$nombre = 'cosmos';
$expiracion = time() + 60 * 30;
$ruta = '/';
$dominio = "";
$seguridad = false; 
$solohttp = true; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $digest = generarDigest($usuario);
    setcookie($nombre, $digest, $expiracion, $ruta, $dominio, $seguridad, $solohttp);
    echo json_encode(array('success' => true, 'message' => 'Cookie creada correctamente.', 'digest' => $digest));
} else {
    echo json_encode(array('success' => false, 'message' => 'Los datos del formulario no se han recibido correctamente.'));
}
?>
