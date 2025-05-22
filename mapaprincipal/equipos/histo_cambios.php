<?php 
require 'conexion.php';
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    die("Debe iniciar sesión para realizar esta acción");
}

// Función para registrar cambios en el historial
function registrarCambio($conexion, $tipo, $detalle, $usuario_id) {
    $sql = "INSERT INTO historial_cambios (tipo_cambio, detalle, realizado_por) 
            VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssi", $tipo, $detalle, $usuario_id);
    return $stmt->execute();
}

// Procesar formulario de equipos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_equipo = isset($_POST['id_equipo']) ? $_POST['id_equipo'] : null;
    
    // Recoger datos del formulario
    $nombre_equipo = $_POST['nombre_equipo'];
    $descripcion = $_POST['descripcion'];
    $tipo_equipo = $_POST['tipo_equipo'];
    $cantidad_total = $_POST['cantidad_total'];
    $cantidad_disponible = $_POST['cantidad_disponible'];
    $serie = $_POST['serie'];
    $estado = $_POST['estado'];
    $estacion = $_POST['estacion'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $tip_equip = $_POST['tip_equip'];

    if ($id_equipo) {
        // Actualizar equipo existente
        $sql = "UPDATE equipos SET 
                nombre_equipo = ?, 
                descripcion = ?, 
                tipo_equipo = ?, 
                cantidad_total = ?, 
                cantidad_disponible = ?, 
                serie = ?, 
                estado = ?, 
                estacion = ?, 
                marca = ?, 
                modelo = ?, 
                tip_equip = ? 
                WHERE id_equipo = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssiissssssi", $nombre_equipo, $descripcion, $tipo_equipo, $cantidad_total, 
                          $cantidad_disponible, $serie, $estado, $estacion, $marca, $modelo, $tip_equip, $id_equipo);

        if ($stmt->execute()) {
            // Registrar en historial
            $detalle = "Se editó el equipo: $nombre_equipo (ID: $id_equipo)";
            registrarCambio($conexion, 'edicion', $detalle, $_SESSION['usuario_id']);
            
            $message = "Equipo actualizado correctamente!";
        }
    } else {
        // Insertar nuevo equipo
        $sql = "INSERT INTO equipos (nombre_equipo, descripcion, tipo_equipo, cantidad_total, cantidad_disponible, serie, estado, estacion, marca, modelo, tip_equip)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssiissssss", $nombre_equipo, $descripcion, $tipo_equipo, $cantidad_total, 
                          $cantidad_disponible, $serie, $estado, $estacion, $marca, $modelo, $tip_equip);

        if ($stmt->execute()) {
            $id_nuevo = $conexion->insert_id;
            
            // Registrar en historial
            $detalle = "Se añadió el equipo: $nombre_equipo (ID: $id_nuevo)";
            registrarCambio($conexion, 'creacion', $detalle, $_SESSION['usuario_id']);
            
            $message = "Equipo agregado correctamente!";
        }
    }

    if (isset($message)) {
        echo "<script>alert('$message'); window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error al guardar el equipo');</script>";
    }
    
    if (isset($stmt)) $stmt->close();
}

// Procesar duplicación de equipo
if (isset($_GET['duplicar'])) {
    $id_original = $_GET['duplicar'];
    
    // Obtener equipo original
    $sql = "SELECT * FROM equipos WHERE id_equipo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_original);
    $stmt->execute();
    $equipo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($equipo) {
        // Insertar equipo duplicado
        $sql = "INSERT INTO equipos (nombre_equipo, descripcion, tipo_equipo, cantidad_total, cantidad_disponible, serie, estado, estacion, marca, modelo, tip_equip)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $nuevo_nombre = $equipo['nombre_equipo'] . " (Copia)";
        $nueva_serie = ""; // Limpiar serie para el duplicado
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssiissssss", 
            $nuevo_nombre, 
            $equipo['descripcion'], 
            $equipo['tipo_equipo'], 
            $equipo['cantidad_total'], 
            $equipo['cantidad_disponible'], 
            $nueva_serie, 
            $equipo['estado'], 
            $equipo['estacion'], 
            $equipo['marca'], 
            $equipo['modelo'], 
            $equipo['tip_equip']
        );
        
        if ($stmt->execute()) {
            $id_nuevo = $conexion->insert_id;
            
            // Registrar en historial
            $detalle = "Se duplicó el equipo: {$equipo['nombre_equipo']} (ID original: $id_original, nuevo ID: $id_nuevo)";
            registrarCambio($conexion, 'duplicacion', $detalle, $_SESSION['usuario_id']);
            
            $message = "Equipo duplicado correctamente!";
            echo "<script>alert('$message'); window.location.href = window.location.href;</script>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
<!-- Sección del historial -->
<div style="margin-top: 30px; background: #212121; padding: 20px; border-radius: 10px;">
    <h2 style="color: #b2eccf;">Historial de Cambios</h2>
    
    <div style="margin-bottom: 15px;">
        <input type="text" id="filtroHistorial" placeholder="Buscar en el historial..." 
               style="padding: 8px; border-radius: 5px; border: none; width: 300px;">
    </div>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #333; color: #b2eccf;">
                <th style="padding: 10px; text-align: left;">Tipo</th>
                <th style="padding: 10px; text-align: left;">Detalle</th>
                <th style="padding: 10px; text-align: left;">Fecha</th>
                <th style="padding: 10px; text-align: left;">Usuario</th>
            </tr>
        </thead>
        <tbody>
            <?php while($cambio = $result_historial->fetch_assoc()): ?>
            <tr style="border-bottom: 1px solid #444;">
                <td style="padding: 10px;"><?= htmlspecialchars($cambio['tipo_cambio']) ?></td>
                <td style="padding: 10px;"><?= htmlspecialchars($cambio['detalle']) ?></td>
                <td style="padding: 10px;"><?= $cambio['fecha'] ?></td>
                <td style="padding: 10px;"><?= $cambio['realizado_por'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
// Filtrado del historial
document.getElementById('filtroHistorial').addEventListener('input', function() {
    const filtro = this.value.toLowerCase();
    const filas = document.querySelectorAll('tbody tr');
    
    filas.forEach(fila => {
        const textoFila = fila.textContent.toLowerCase();
        fila.style.display = textoFila.includes(filtro) ? '' : 'none';
    });
});
</script>
</body>
</html>