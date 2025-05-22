<?php
require "conexion.php";

header("Content-Type: application/json");

if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $sql = "SELECT * FROM equipos WHERE id_equipo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(null);
    }
} else {
    echo json_encode(null);
}
?>