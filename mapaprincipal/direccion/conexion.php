<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "gestion_equipos_telecom";

// Crear conexión
$conexion = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>
