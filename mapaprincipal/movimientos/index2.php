<?php
include('conexion.php'); // Asumo que esta conexión es $conn (mysqli)
require('conexion1.php'); // Asumo que esta conexión es $conexion (mysqli) - ¡Cuidado con tener dos conexiones! Usaré $conn para las operaciones principales.
session_start();

if (!isset($_SESSION['nombre'])) {
    header('Location: http://localhost/nuevo/contrase%C3%B1a/indexlogin.php');
    exit();
}

$nombre_sesion = $_SESSION['nombre'];

// Obtener usuario actual
if ($stmt = $conexion->prepare("SELECT nombre, foto_rostro FROM usuarios WHERE nombre = ?")) {
    $stmt->bind_param("s", $nombre_sesion);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Error en la consulta: " . $conexion->error);
}

if (!$user) {
    die("Usuario no encontrado.");
}

$imagen = $user['foto_rostro']
    ? '../uploads/perfiles/' . $user['foto_rostro']
    : 'assets/img/avatar-default.png';

function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

$alert = "";
$alert_class = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_equipo'])) {
    $id_equipo                = sanitize_input($_POST['id_equipo']);
    // <<< CAMBIO CLAVE 1: Recoger el ID del usuario del formulario
    $id_usuario_seleccionado  = sanitize_input($_POST['id_usuario_form']); // El select enviará el ID
    $cantidad                 = sanitize_input($_POST['cantidad']);
    $tipo                     = sanitize_input($_POST['tipo']);
    $estado_entrega           = isset($_POST['estado_entrega']) ? sanitize_input($_POST['estado_entrega']) : NULL;
    $observacion              = sanitize_input($_POST['observacion']);
    $fecha_salida             = date('Y-m-d H:i:s');
    $fecha_devolucion         = ($tipo === 'Devolución') ? date('Y-m-d H:i:s') : NULL;

    if (empty($id_usuario_seleccionado) || !is_numeric($id_usuario_seleccionado)) {
        $alert = "Debe seleccionar un usuario válido.";
        $alert_class = "alert-danger";
    } elseif (!is_numeric($cantidad) || $cantidad <= 0) {
        $alert = "La cantidad debe ser un número positivo.";
        $alert_class = "alert-danger";
    } else {
        $conn->begin_transaction();

        try {
            // <<< CAMBIO CLAVE 2: Obtener el nombre del usuario basado en el ID seleccionado
            $nombre_usuario_para_guardar = '';
            $sql_get_username = "SELECT nombre FROM usuarios WHERE id_usuario = ? LIMIT 1";
            $stmt_get_username = $conn->prepare($sql_get_username);
            $stmt_get_username->bind_param('i', $id_usuario_seleccionado);
            $stmt_get_username->execute();
            $stmt_get_username->bind_result($nombre_usuario_para_guardar);
            if (!$stmt_get_username->fetch()) {
                $stmt_get_username->close();
                throw new Exception("Usuario seleccionado no encontrado.");
            }
            $stmt_get_username->close();


            $sql_equipo = "SELECT e.id_equipo, e.nombre_equipo, e.cantidad_disponible
                          FROM equipos e
                          WHERE e.id_equipo = ? FOR UPDATE";
            $stmt_equipo = $conn->prepare($sql_equipo);
            $stmt_equipo->bind_param('i', $id_equipo);
            $stmt_equipo->execute();
            $stmt_equipo->bind_result($id_equipo_db, $nombre_equipo, $cantidad_disponible);
            $stmt_equipo->fetch();
            $stmt_equipo->close();

            if (!$id_equipo_db) {
                throw new Exception("El equipo seleccionado no existe.");
            }
            if ($cantidad_disponible < $cantidad) {
                throw new Exception("No hay suficiente stock de $nombre_equipo. Disponibles: $cantidad_disponible");
            }

            // <<< CAMBIO CLAVE 3: Insertar id_usuario y nombre_usuario en movimientos
            // Asegúrate que tu tabla 'movimientos' tenga las columnas 'id_usuario' (INT) y 'nombre_usuario' (VARCHAR)
            $sql_movimiento = "INSERT INTO movimientos
                               (id_equipo, id_usuario, nombre_usuario, cantidad, fecha_salida, fecha_entrega, estado_entrega, observacion, nombre_equipo)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; // Añadido id_usuario y nombre_usuario
            $stmt_movimiento = $conn->prepare($sql_movimiento);
            // 'i' para id_usuario, 's' para nombre_usuario
            $stmt_movimiento->bind_param('iisisssss', $id_equipo, $id_usuario_seleccionado, $nombre_usuario_para_guardar, $cantidad,
                                       $fecha_salida, $fecha_devolucion, $estado_entrega, $observacion, $nombre_equipo);

            if (!$stmt_movimiento->execute()) {
                throw new Exception("Error al registrar movimiento: " . $stmt_movimiento->error);
            }

            $id_movimiento = $conn->insert_id;
            $stmt_movimiento->close();

            $nueva_cantidad = $cantidad_disponible - $cantidad;
            $sql_update = "UPDATE equipos SET cantidad_disponible = ? WHERE id_equipo = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('ii', $nueva_cantidad, $id_equipo);
            if (!$stmt_update->execute()) {
                throw new Exception("Error al actualizar stock: " . $stmt_update->error);
            }
            $stmt_update->close();

            // En estado_entregas, guardamos el nombre del usuario
            $sql_estado = "INSERT INTO estado_entregas
                                    (id_movimiento, entregado, observacion, nombre_equipo, nombre_usuario)
                                    VALUES (?, 'No', ?, ?, ?)";
            $stmt_estado = $conn->prepare($sql_estado);
            $stmt_estado->bind_param('isss', $id_movimiento, $observacion, $nombre_equipo, $nombre_usuario_para_guardar);
            if (!$stmt_estado->execute()) {
                throw new Exception("Error al registrar estado: " . $stmt_estado->error);
            }
            $stmt_estado->close();

            // En historial_cambios, guardamos el nombre del usuario en 'realizado_por' (asumiendo que es VARCHAR)
            // Si 'realizado_por' debe ser un ID, entonces usa $id_usuario_seleccionado
            $sql_historial = "INSERT INTO historial_cambios
                                        (tipo_cambio, detalle, realizado_por)
                                        VALUES ('Movimiento', ?, ?)";
            $detalle = "Movimiento registrado: $nombre_equipo (ID: $id_equipo), Cantidad: $cantidad, Usuario: $nombre_usuario_para_guardar (ID: $id_usuario_seleccionado)";
            $stmt_historial = $conn->prepare($sql_historial);
            // Si realizado_por es ID usa 'si', $id_usuario_seleccionado. Si es nombre usa 'ss', $nombre_usuario_para_guardar
            $stmt_historial->bind_param('ss', $detalle, $nombre_usuario_para_guardar); // Asumiendo que realizado_por es VARCHAR
            $stmt_historial->execute();
            $stmt_historial->close();

            $conn->commit();
            $alert = "Movimiento de $nombre_equipo registrado correctamente. Usuario: $nombre_usuario_para_guardar. Estado marcado como 'No entregado'.";
            $alert_class = "alert-success";

        } catch (Exception $e) {
            $conn->rollback();
            if ($e instanceof mysqli_sql_exception) {
                $alert = "Error de base de datos: " . $e->getMessage();
            } else {
                $alert = "Error: " . $e->getMessage();
            }
            $alert_class = "alert-danger";
        }
    }
  }

// Buscar por nombre de equipo si se ha enviado desde el formulario
$busqueda = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';

// Consultar equipos entregados
$sqlEntregados = "SELECT ee.*, u.nombre as nombre_usuario_entregado FROM estado_entregas ee LEFT JOIN movimientos m ON ee.id_movimiento = m.id_movimiento LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario WHERE ee.entregado = 'Sí'";
if ($busqueda !== '') {
    $sqlEntregados .= " AND ee.nombre_equipo LIKE '%$busqueda%'";
}
$resultadoEntregados = $conexion->query($sqlEntregados);

// Consultar equipos pendientes
$sqlPendientes = "SELECT ee.*, u.nombre as nombre_usuario_pendiente FROM estado_entregas ee LEFT JOIN movimientos m ON ee.id_movimiento = m.id_movimiento LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario WHERE ee.entregado = 'No'";
if ($busqueda !== '') {
    $sqlPendientes .= " AND ee.nombre_equipo LIKE '%$busqueda%'";
}
$resultadoPendientes = $conexion->query($sqlPendientes);


// Procesar actualización de estado si se envió el formulario (desde la lista de pendientes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_movimiento_actualizar'])) {
    $id_mov_actualizar = $_POST['id_movimiento_actualizar'];
    $nuevoEstado = $conexion->real_escape_string($_POST['nuevo_estado']);
    $observacion_actualizar = $conexion->real_escape_string($_POST['observacion_actualizar']);

    $stmt_update_estado = $conexion->prepare("UPDATE estado_entregas SET entregado=?, observacion=?, fecha_actualizacion=NOW() WHERE id_movimiento=?");
    $stmt_update_estado->bind_param('ssi', $nuevoEstado, $observacion_actualizar, $id_mov_actualizar);
    $stmt_update_estado->execute();
    $stmt_update_estado->close();

    if ($nuevoEstado === 'Sí') {
        $stmt_update_mov = $conexion->prepare("UPDATE movimientos SET fecha_entrega=NOW() WHERE id_movimiento=?");
        $stmt_update_mov->bind_param('i', $id_mov_actualizar);
        $stmt_update_mov->execute();
        $stmt_update_mov->close();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$imagen = 'ruta/a/tu/imagen_perfil.jpg';
?>
<!DOCTYPE html>  
<html lang="es">  
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>EQUIPOS PERMANENTES</title>  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../volver.css">
    <link rel="stylesheet" href="styles1.css">

    <style>
        /* CENTRAR Y ADAPTAR CONTENEDOR */
        .movi {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: start;
            padding: 30px 15px;
            box-sizing: border-box;
            margin-top:90px;
            position:relative;

        }

        .movi .card {
            background-color:rgba(48, 48, 48, 0.73);
            width: 100%;
            max-width: 800px;
            box-shadow: 0 4px 20px rgba(104, 104, 104, 0.7);
            color:white;
            border-radius: 4%;  
        }

        .select2-container .select2-selection--single {
            height: 38px;
            padding: 6px 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px;
        }

        @media (max-width: 576px) {
            .movi {
                padding: 20px 10px;
            }
        }
        .barra h2{
            color:white;
            z-index:1;
            margin-left:600px;
            margin-top:5px;
        }
        .body{
            
        }
.totalcont{
 width: 100%;
        height: 100%;
        position: relative;
        display: flex;
        flex-direction: column; 
}
.contentfor{
        width: 50%;
        height: 50%;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color:rgba(240, 240, 240, 0); /* Solo para visualización */
        margin-top:100px;
        }
.superior{
  display: flex; /* Para poner Izquierda y Derecha uno al lado del otro */
        width: 100%;
        height: 70%; /* Puedes ajustar esta altura */
        margin-top:90px;
        position:relative;
}
    .perfil {
      position: fixed;
      margin-top: 5px;
      margin-left: 95%;
      width: 45px;  /* Tamaño del círculo */
      height: 45px; 
      border-radius: 50%;  /* Hace que sea un círculo */
      overflow: hidden;  /* Evita que la imagen se salga */
      border: 3px solid white;  /* Borde elegante */
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.5); /* Sombra para resaltar */
  }
  
    </style>
</head>
<body>  

<div class="totalcont">  

<div class="barra">
      <!-- From Uiverse.io by xopc333 --> 
  <a href="http://localhost/nuevo/contraseña/mapaprincipal/index.php"><button class="button"> 
  <div class="button-box">
    <span class="button-elem">
      <svg viewBox="0 0 46 40" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M46 20.038c0-.7-.3-1.5-.8-2.1l-16-17c-1.1-1-3.2-1.4-4.4-.3-1.2 1.1-1.2 3.3 0 4.4l11.3 11.9H3c-1.7 0-3 1.3-3 3s1.3 3 3 3h33.1l-11.3 11.9c-1 1-1.2 3.3 0 4.4 1.2 1.1 3.3.8 4.4-.3l16-17c.5-.5.8-1.1.8-1.9z"
        ></path>
      </svg>
    </span>
    <span class="button-elem">
      <svg viewBox="0 0 46 40">
        <path
          d="M46 20.038c0-.7-.3-1.5-.8-2.1l-16-17c-1.1-1-3.2-1.4-4.4-.3-1.2 1.1-1.2 3.3 0 4.4l11.3 11.9H3c-1.7 0-3 1.3-3 3s1.3 3 3 3h33.1l-11.3 11.9c-1 1-1.2 3.3 0 4.4 1.2 1.1 3.3.8 4.4-.3l16-17c.5-.5.8-1.1.8-1.9z"
        ></path>
      </svg>
    </span>
  </div>
</button>
</a>
    <h2>Gestionar moviento de equipos</h2>  
  <div class="perfil">
 <img src="<?= htmlspecialchars($imagen) ?>" alt="Foto de perfil">
</div>   
</div>
<div class="superior">
<div class="contentfor">
            <div class="movi">
              <div class="card">
                <h2 class="mb-4 text-center">Registro de Movimientos</h2>

                <?php if(!empty($alert)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo $alert; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label for="id_equipo" class="form-label">Equipo</label>
                      <select name="id_equipo" id="id_equipo" class="form-select" required>
                        <option value="">Seleccione un equipo</option>
                        <?php
                        $sql_equipos_form = "SELECT id_equipo, IFNULL(nombre_equipo, 'Sin nombre') AS nombre_equipo, IFNULL(estacion, 'Sin estación') AS estacion, IFNULL(serie, 'Sin serie') AS serie, IFNULL(cantidad_disponible, 0) AS cantidad_disponible FROM equipos ORDER BY nombre_equipo";
                        $result_equipos_form = $conn->query($sql_equipos_form);
                        while ($equipo = $result_equipos_form->fetch_assoc()) {
                            $texto = htmlspecialchars("{$equipo['nombre_equipo']} - Est: {$equipo['estacion']} - Serie: {$equipo['serie']}");
                            $texto .= ($equipo['cantidad_disponible'] <= 0) ? " (Sin stock)" : " (Disp: {$equipo['cantidad_disponible']})";
                            echo "<option value='" . $equipo['id_equipo'] . "'>" . $texto . "</option>";
                        }
                        ?>
                      </select>
                    </div>

                    <div class="col-md-6">
                      <!-- <<< CAMBIO CLAVE 4: El select de usuario ahora envía id_usuario -->
                      <label for="id_usuario_select" class="form-label">Operador/Usuario</label>
                      <select name="id_usuario_form" id="id_usuario_select" class="form-select" required>
                        <option value="">Seleccione un usuario</option>
                        <?php
                        // El value de la opción será el id_usuario
                        $sql_usuarios_form = "SELECT id_usuario, nombre FROM usuarios ORDER BY nombre";
                        $result_usuarios_form = $conn->query($sql_usuarios_form);
                        while ($usuario = $result_usuarios_form->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($usuario['id_usuario']) . "'>" . htmlspecialchars($usuario['nombre']) . "</option>";
                        }
                        ?>
                      </select>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label for="cantidad" class="form-label">Cantidad</label>
                      <input type="number" name="cantidad" id="cantidad" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="col-md-4">
                      <label for="tipo" class="form-label">Tipo de Movimiento</label>
                      <select name="tipo" id="tipo" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option value="Entrega">Entrega</option>
                        <option value="Devolución">Devolución</option>
                      </select>
                    </div>
                    <div class="col-md-4" id="estado-entrega-container" style="display: none;">
                      <label for="estado_entrega" class="form-label">Estado de Entrega</label>
                      <select name="estado_entrega" id="estado_entrega" class="form-select">
                        <option value="">Seleccione</option>
                        <option value="Bueno">Bueno</option>
                        <option value="Malo">Malo</option>
                        <option value="Incompleto">Incompleto</option>
                      </select>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label for="observacion" class="form-label">Observaciones</label>
                    <textarea name="observacion" id="observacion" class="form-control" rows="3" placeholder="Ingrese detalles adicionales, si lo requiere"></textarea>
                  </div>

                  <div class="mb-3">
                    <label for="evidencias" class="form-label">Subir Evidencias (Opcional)</label>
                    <input type="file" name="evidencias[]" id="evidencias" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf">
                    <div class="form-text" style="color: #ccc;">Formatos: JPG, PNG, PDF. Max 5MB/archivo.</div>
                  </div>

                  <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Registrar Movimiento</button>
                  </div>
                </form>
              </div>
            </div>
        </div>
      <style>
       .contenedor_de_panels {
       display:flex; justify-content: center; overflow-y: auto;   width: 50%; height: 95%;
    
}


       
      
        .equipo { background-color: rgb(58, 58, 58); border-left: 4px solid rgb(68, 243, 33); padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9em; color:white; }
        .panel:last-child .equipo { border-left-color: rgb(243, 180, 33); }
        .equipo strong { color: white; }
        .equipo form { margin-top: 0px; }
        .equipo select, .equipo input[type="text"] { width: calc(100% - 16px); margin: 1px 20px; padding: 8px; border: 1px solid #555; border-radius: 4px; background-color: #444; color: white; }
        .equipo button[type="submit"] { background-color: rgb(0, 143, 5); color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 5px; }

.equipo {
    background-color:rgb(53, 53, 53);
    border-left: 4px solidrgb(68, 243, 33);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    transition: background-color 0.3s ease;
}

.equipo:hover {
    background-color:rgb(129, 129, 129);
}

.equipo strong {
    color: white;
}

form {
    margin-top: 10px;
}

select, input[type="text"] {
    width: 100%;
    padding: 8px;
    margin: 6px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 14px;
}

button[type="submit"] {
    background-color:rgb(0, 148, 5);
    color: white;
    padding: 10px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
   
    transition: background-color 0.3s;
}

button[type="submit"]:hover {
    background-color:rgb(48, 209, 56);
}
  .contenidoInferior{
        width: 100%;
        height: 20%; /* Puedes ajustar esta altura */
        background-color:rgba(208, 208, 208, 0); /* Solo para visualización */
        display: flex;
        margin-top:50px;
        justify-content: center; 
        position: relative;
    }
 .search-field input.input { background: transparent; color: white; }
      </style>

<div class="contenedor_de_panels">
            <div class="panel" style="margin: 0px 40px;">
                <h2 style="color:white;">Equipos Entregados ✅</h2>
                <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="search" style="margin-bottom: 15px;">
                      <div class="search-box"><div class="search-field">
                          <input placeholder="Buscar equipo entregado..." class="input" type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>">
                          <div class="search-box-icon"><button class="btn-icon-content" type="submit"><i class="search-icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 512 512"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"></path></svg></i></button></div>
                      </div></div>
                    </div>
                </form>
                <?php if ($resultadoEntregados && $resultadoEntregados->num_rows > 0): ?>
                    <?php while($fila = $resultadoEntregados->fetch_assoc()): ?>
                        <div class="equipo"  style="">
                            <strong>Equipo:</strong> <?= htmlspecialchars($fila['nombre_equipo']) ?><br>
                            <!-- Usar el nombre de usuario que viene de estado_entregas o el unido desde usuarios -->
                            <strong>Usuario:</strong> <?= htmlspecialchars($fila['nombre_usuario_entregado'] ?? $fila['nombre_usuario']) ?> <br>
                            <strong>ID Mov:</strong> <?= $fila['id_movimiento'] ?><br>
                            <strong>Fecha Entrega:</strong> <?= htmlspecialchars($fila['fecha_actualizacion'] ? date('d/m/Y H:i', strtotime($fila['fecha_actualizacion'])) : 'N/A') ?><br>
                            <strong>Obs:</strong> <?= htmlspecialchars($fila['observacion'] ?: 'Sin observaciones') ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No hay equipos entregados<?php if($busqueda) echo " que coincidan con '$busqueda'"; ?>.</p>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h2 style="color:white;">Equipos Pendientes ⏳</h2>
                <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                     <div class="search" style="margin-bottom: 15px;">
                        <div class="search-box"><div class="search-field">
                            <input placeholder="Buscar equipo pendiente..." class="input" type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>">
                            <div class="search-box-icon"><button class="btn-icon-content" type="submit"><i class="search-icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 512 512"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"></path></svg></i></button></div>
                        </div></div>
                    </div>
                </form>
                <?php if ($resultadoPendientes && $resultadoPendientes->num_rows > 0): ?>
                    <?php while($fila = $resultadoPendientes->fetch_assoc()): ?>
                        <div class="equipo">
                            <strong>Equipo:</strong> <?= htmlspecialchars($fila['nombre_equipo']) ?><br>
                             <!-- Usar el nombre de usuario que viene de estado_entregas o el unido desde usuarios -->
                            <strong>Usuario:</strong> <?= htmlspecialchars($fila['nombre_usuario_pendiente'] ?? $fila['nombre_usuario']) ?> <br>
                            <strong>ID Mov:</strong> <?= $fila['id_movimiento'] ?><br>
                            <strong>Últ. Act:</strong> <?= htmlspecialchars($fila['fecha_actualizacion'] ? date('d/m/Y H:i', strtotime($fila['fecha_actualizacion'])) : ($fila['fecha_creacion'] ? date('d/m/Y H:i', strtotime($fila['fecha_creacion'])) : 'N/A' )) ?><br>
                            <strong>Obs:</strong> <?= htmlspecialchars($fila['observacion'] ?: 'Sin observaciones') ?><br>

                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <input type="hidden" name="id_movimiento_actualizar" value="<?= $fila['id_movimiento'] ?>">
                                <label for="nuevo_estado_<?= $fila['id_movimiento'] ?>">Estado:</label>
                                <select name="nuevo_estado" id="nuevo_estado_<?= $fila['id_movimiento'] ?>" required>
                                    <option value="Sí">Entregado</option><option value="No" disabled>Pendiente</option>
                                </select><br>
                                <label for="observacion_actualizar_<?= $fila['id_movimiento'] ?>">Descripción:</label>
                                <input type="text" name="observacion_actualizar" id="observacion_actualizar_<?= $fila['id_movimiento'] ?>" placeholder="Ingrese descripción" required><br>
                                <button type="submit">Marcar como entregado</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Todos los equipos han sido entregados o no hay pendientes<?php if($busqueda) echo " que coincidan con '$busqueda'"; ?> 🎉</p>
                <?php endif; ?>
            </div>
        </div>
</div> 
<div class="contenidoInferior">
        <h3 class="text-center text-white mt-4 mb-3" style="margin: 1px 20px;">Historial de <br>
         Movimientos</h3>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th>ID Mov.</th>
                        <th>Equipo (ID)</th>
                        <th>Usuario (Nombre)</th> <!-- Mostraremos el nombre -->
                        <th>ID Usuario</th> <!-- Opcional: mostrar también el ID -->
                        <th>Cant.</th>
                        <th>Fecha Salida</th>
                        <th>Fecha de entreda</th>
                        <th>Estado Físico</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // <<< CAMBIO CLAVE 5: En la SELECT del historial, seleccionamos id_usuario y nombre_usuario de la tabla movimientos
                    $sql_historial_display = "SELECT id_movimiento, id_equipo, nombre_equipo, id_usuario, nombre_usuario, cantidad, fecha_salida, fecha_entrega, estado_entrega, observacion FROM movimientos ORDER BY id_movimiento DESC LIMIT 20";
                    $result_historial = $conn->query($sql_historial_display);
                    if ($result_historial && $result_historial->num_rows > 0) {
                        while ($row = $result_historial->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['id_movimiento'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['nombre_equipo']) . " (" . $row['id_equipo'] . ")</td>";
                            echo "<td>" . htmlspecialchars($row['nombre_usuario']) . "</td>"; // Nombre del usuario
                            echo "<td>" . htmlspecialchars($row['id_usuario']) . "</td>";     // ID del usuario (FK)
                            echo "<td>" . $row['cantidad'] . "</td>";
                            echo "<td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_salida']))) . "</td>";
                            echo "<td>" . ($row['fecha_entrega'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_entrega']))) : '---') . "</td>";
                            echo "<td>" . ($row['estado_entrega'] ? htmlspecialchars($row['estado_entrega']) : 'N/A') . "</td>";
                            echo "<td>" . ($row['observacion'] ? htmlspecialchars($row['observacion']) : '---') . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        if (!$result_historial) {
                            echo "<tr><td colspan='9'>Error al cargar el historial: " . $conn->error . "</td></tr>"; // Ajustado colspan
                        } else {
                            echo "<tr><td colspan='9'>No se encontraron movimientos.</td></tr>"; // Ajustado colspan
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#id_equipo, #nombre_usuario').select2({ width: '100%' });
        
        $('#tipo').on('change', function() {
            if ($(this).val() === 'Entrega') {
                $('#estado-entrega-container').show();
            } else {
                $('#estado-entrega-container').hide();
            }
        }).trigger('change');
    });
</script>
</body>
</html>