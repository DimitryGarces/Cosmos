<?php
function generarDigest($usuario) {
    return sha1(rand(0, microtime(true)) . $usuario . strval(microtime(true)));
}
?>
